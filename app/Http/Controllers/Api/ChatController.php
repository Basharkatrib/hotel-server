<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    private string $apiKey;
    private string $baseUrl;

    // Primary model + fallback models (tried in order on 429)
    private array $models = [
        'llama-3.3-70b-versatile',   // Primary
        'llama-3.1-8b-instant',      // Fallback 1 — faster, higher limits
        'gemma2-9b-it',              // Fallback 2
    ];

    public function __construct()
    {
        $this->apiKey  = env('GROQ_API_KEY', '');
        $this->baseUrl = env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1');
    }

    // =========================================================================
    // Main entry point
    // =========================================================================
    public function chat(Request $request): JsonResponse
    {
        set_time_limit(120);

        $request->validate([
            'messages'           => 'required|array|min:1',
            'messages.*.role'    => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:2000',
        ]);

        $user = auth('sanctum')->user();

        // Keep only last 6 messages to stay within token limits
        $trimmedMessages = collect($request->messages)
            ->filter(fn($m) => in_array($m['role'] ?? '', ['user', 'assistant']))
            ->values()
            ->slice(-6)
            ->toArray();

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($user)],
            ...$trimmedMessages,
        ];

        try {
            $result = $this->chatWithFallback($messages, $this->buildTools());

            $message = $result['choices'][0]['message']['content'] ?? null;

            if (empty(trim($message ?? ''))) {
                $message = $this->fallbackMessage($trimmedMessages);
            }

            return response()->json(['status' => 'success', 'message' => $message]);

        } catch (\Exception $e) {
            Log::error('ChatController: all models failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'success',
                'message' => $this->groqFallbackMessage($trimmedMessages),
            ]);
        }
    }

    // =========================================================================
    // Try models in order — skip to next on 429 or 403
    // =========================================================================
    private function chatWithFallback(array $messages, array $tools): array
    {
        $lastException = null;

        foreach ($this->models as $index => $model) {
            try {
                Log::info("ChatController: trying model [{$model}]");

                // First call with tools
                $response = $this->callGroq([
                    'model'       => $model,
                    'messages'    => $messages,
                    'tools'       => $tools,
                    'tool_choice' => 'auto',
                    'max_tokens'  => 1024,
                ]);

                $choice       = $response['choices'][0];
                $finishReason = $choice['finish_reason'] ?? '';
                $localMessages = $messages;

                // Tool call loop
                $iterations = 0;
                while ($finishReason === 'tool_calls' && $iterations < 3) {
                    $iterations++;
                    $localMessages[] = $choice['message'];

                    foreach ($choice['message']['tool_calls'] ?? [] as $toolCall) {
                        $toolName = $toolCall['function']['name'];
                        $toolArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];

                        if (isset($toolArgs['limit']))    $toolArgs['limit']    = (int) $toolArgs['limit'];
                        if (isset($toolArgs['hotel_id'])) $toolArgs['hotel_id'] = (int) $toolArgs['hotel_id'];
                        if (isset($toolArgs['limit']) && $toolArgs['limit'] <= 0) unset($toolArgs['limit']);

                        $toolResult     = $this->executeToolCall($toolName, $toolArgs, auth('sanctum')->user());
                        $localMessages[] = [
                            'role'         => 'tool',
                            'tool_call_id' => $toolCall['id'],
                            'content'      => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
                        ];
                    }

                    $response     = $this->callGroq([
                        'model'      => $model,
                        'messages'   => $localMessages,
                        'max_tokens' => 1024,
                    ]);

                    $choice       = $response['choices'][0];
                    $finishReason = $choice['finish_reason'] ?? '';
                }

                return $response; // ✅ Success

            } catch (\Exception $e) {
                $lastException = $e;
                $isRateLimit   = str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'rate_limited');
                $isAccessDenied = str_contains($e->getMessage(), '403') || str_contains($e->getMessage(), 'access_denied');

                if ($isRateLimit || $isAccessDenied) {
                    $nextModel = $this->models[$index + 1] ?? null;
                    Log::warning("ChatController: model [{$model}] failed ({$e->getMessage()}). " . ($nextModel ? "Trying [{$nextModel}]" : "No more fallbacks."));
                    continue; // Try next model
                }

                throw $e; // Non-rate-limit error — don't retry
            }
        }

        throw $lastException ?? new \Exception('All models failed.');
    }

    // =========================================================================
    // Groq HTTP helper
    // =========================================================================
    private function callGroq(array $payload): array
    {
        $response = Http::timeout(60)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post("{$this->baseUrl}/chat/completions", $payload);

        if ($response->status() === 429) {
            throw new \Exception('rate_limited_429');
        }

        if ($response->status() === 403) {
            throw new \Exception('access_denied_403');
        }

        if ($response->failed()) {
            throw new \Exception('Groq error ' . $response->status() . ': ' . $response->body());
        }

        return $response->json();
    }

    // =========================================================================
    // Fallback messages
    // =========================================================================
    private function groqFallbackMessage(array $messages): string
    {
        $lastContent = collect($messages)
            ->filter(fn($m) => ($m['role'] ?? '') === 'user')
            ->last()['content'] ?? '';

        $isArabic = preg_match('/\p{Arabic}/u', $lastContent);

        return $isArabic
            ? 'عذراً، الخادم مشغول حالياً. يرجى الانتظار لحظة ثم المحاولة مجدداً 😊'
            : "The server is a bit busy right now. Please wait a moment and try again! 😊";
    }

    private function fallbackMessage(array $messages): string
    {
        $lastContent = collect($messages)->last()['content'] ?? '';
        $isArabic    = preg_match('/\p{Arabic}/u', $lastContent);

        return $isArabic
            ? 'عذراً، لم أتمكن من معالجة طلبك. هل يمكنك إعادة صياغة السؤال؟ 😊'
            : "I couldn't process that. Could you rephrase your question? 😊";
    }

    // =========================================================================
    // System prompt
    // =========================================================================
    private function buildSystemPrompt($user): string
    {
        $userName = $user ? $user->name : 'a visitor';
        $isAuth   = $user ? 'yes' : 'no';
        $today    = now()->format('Y-m-d');

        return <<<EOT
You are Vayka AI, the intelligent concierge assistant for the Vayka hotel booking platform.
Today's date is {$today}.
You are speaking with: {$userName} (authenticated: {$isAuth}).

=== LANGUAGE RULE (CRITICAL) ===
- Arabic message  → reply ENTIRELY in Arabic.
- English message → reply ENTIRELY in English.
- NEVER mix languages.

=== UNKNOWN QUESTIONS ===
If outside your scope: politely say so in the user's language and suggest support@vayka.com.

=== TOOLS — ALWAYS CALL BEFORE ANSWERING ===
| Tool               | When to use                                                             |
|--------------------|-------------------------------------------------------------------------|
| list_all_hotels    | "الفنادق", "كل الفنادق", "show hotels", "all hotels"                   |
| search_hotels      | city/country mentioned: "دبي", "Spain", "Dubai", "Amman"               |
| get_room_details   | "الغرف", "أسعار الغرف", "rooms", "room prices"                         |
| get_user_bookings  | "حجوزاتي", "my bookings", "reservations"                               |
| compare_hotels     | "قارن", "الفرق بين", "مقارنة", "compare", "difference between"          |

=== RESPONSE FORMAT ===
Hotels:
1. [Name] — 📍 [City, Country] | ⭐ [rating] | 💰 [price]/night | 🚪 [N] rooms available | 🔗 /hotel/[slug]

Rooms:
1. [Name] — 🏨 [Hotel] | 🛏 [type] | 💰 [price]/night | 👥 [N] guests | ✅/❌ Available

Bookings:
1. 🏨 [Hotel] — [Room] | 📅 [in] → [out] | 🌙 [N] nights | 👥 [N] guests | 💰 [total]
   ✅ Confirmed / ⏳ Pending / ❌ Cancelled / 🏁 Completed

Comparison:
Use a structured list or a table-like format to compare hotels based on rating, price, amenities, and location.

Rules: warm, concise, professional. Never fabricate data. Support: support@vayka.com
EOT;
    }

    // =========================================================================
    // Tool definitions (limit removed from schema)
    // =========================================================================
    private function buildTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'list_all_hotels',
                    'description' => 'List all hotels sorted by rating. Use when user asks about all hotels.',
                    'parameters'  => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'search_hotels',
                    'description' => 'Search hotels by city or country.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'city' => ['type' => 'string', 'description' => 'City or country name'],
                        ],
                        'required' => ['city'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_room_details',
                    'description' => 'Get room types, prices, and availability.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'hotel_id'       => ['type' => 'integer', 'description' => 'Filter by hotel ID (optional)'],
                            'only_available' => ['type' => 'boolean', 'description' => 'Show only available rooms'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_user_bookings',
                    'description' => "Get the user's booking history.",
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'status' => [
                                'type'        => 'string',
                                'description' => 'Filter by status',
                                'enum'        => ['all', 'pending', 'confirmed', 'cancelled', 'completed'],
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'compare_hotels',
                    'description' => 'Fetch detailed information for two or more hotels to compare them. Use when user asks for a comparison between specific hotels.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'hotel_names' => [
                                'type'        => 'array',
                                'items'       => ['type' => 'string'],
                                'description' => 'List of hotel names to compare',
                            ],
                        ],
                        'required' => ['hotel_names'],
                    ],
                ],
            ],
        ];
    }

    // =========================================================================
    // Tool router
    // =========================================================================
    private function executeToolCall(string $name, array $args, $user): array
    {
        try {
            return match ($name) {
                'list_all_hotels'   => $this->toolListAllHotels(),
                'search_hotels'     => $this->toolSearchHotels($args),
                'get_room_details'  => $this->toolGetRoomDetails($args),
                'get_user_bookings' => $this->toolGetUserBookings($args, $user),
                'compare_hotels'    => $this->toolCompareHotels($args),
                default             => ['error' => "Unknown tool: {$name}"],
            };
        } catch (\Exception $e) {
            Log::error("Tool [{$name}] failed", ['error' => $e->getMessage()]);
            return ['error' => "Tool [{$name}] encountered an error."];
        }
    }

    private function toolListAllHotels(): array
    {
        $hotels = Hotel::select('id', 'name', 'city', 'country', 'price_per_night', 'rating', 'type', 'slug')
            ->withCount(['rooms as available_rooms' => fn($q) => $q->where('is_available', true)])
            ->orderBy('rating', 'desc')
            ->limit(20)
            ->get();

        if ($hotels->isEmpty()) {
            return ['found' => false, 'message' => 'No hotels listed.'];
        }

        return [
            'found'  => true,
            'total'  => $hotels->count(),
            'hotels' => $hotels->map(fn($h) => [
                'id'              => $h->id,
                'name'            => $h->name,
                'location'        => "{$h->city}, {$h->country}",
                'type'            => $h->type,
                'rating'          => $h->rating,
                'price_per_night' => '$' . number_format($h->price_per_night, 0),
                'available_rooms' => $h->available_rooms,
                'link'            => "/hotel/{$h->slug}",
            ])->toArray(),
        ];
    }

    private function toolSearchHotels(array $args): array
    {
        $city = trim($args['city'] ?? '');

        if (empty($city)) {
            return $this->toolListAllHotels();
        }

        $hotels = Hotel::where(function ($q) use ($city) {
                $q->where('city', 'like', "%{$city}%")
                  ->orWhere('country', 'like', "%{$city}%")
                  ->orWhere('name', 'like', "%{$city}%");
            })
            ->select('id', 'name', 'city', 'country', 'price_per_night', 'rating', 'type', 'slug')
            ->withCount(['rooms as available_rooms' => fn($q) => $q->where('is_available', true)])
            ->orderBy('rating', 'desc')
            ->limit(10)
            ->get();

        if ($hotels->isEmpty()) {
            return ['found' => false, 'message' => "No hotels found in '{$city}'."];
        }

        return [
            'found'  => true,
            'city'   => $city,
            'total'  => $hotels->count(),
            'hotels' => $hotels->map(fn($h) => [
                'id'              => $h->id,
                'name'            => $h->name,
                'location'        => "{$h->city}, {$h->country}",
                'type'            => $h->type,
                'rating'          => $h->rating,
                'price_per_night' => '$' . number_format($h->price_per_night, 0),
                'available_rooms' => $h->available_rooms,
                'link'            => "/hotel/{$h->slug}",
            ])->toArray(),
        ];
    }

    private function toolGetRoomDetails(array $args): array
    {
        $hotelId       = isset($args['hotel_id']) ? (int) $args['hotel_id'] : null;
        $onlyAvailable = (bool) ($args['only_available'] ?? false);

        $query = Room::with('hotel:id,name,city,slug')
            ->where('is_active', true)
            ->select('id', 'hotel_id', 'name', 'type', 'price_per_night', 'max_guests', 'is_available');

        if ($hotelId) $query->where('hotel_id', $hotelId);
        if ($onlyAvailable) $query->where('is_available', true);

        $rooms = $query->orderBy('price_per_night')->limit(10)->get();

        if ($rooms->isEmpty()) {
            return ['found' => false, 'message' => 'No rooms found.'];
        }

        return [
            'found' => true,
            'total' => $rooms->count(),
            'rooms' => $rooms->map(fn($r) => [
                'name'            => $r->name,
                'hotel'           => $r->hotel?->name,
                'hotel_city'      => $r->hotel?->city,
                'type'            => $r->type,
                'price_per_night' => '$' . number_format($r->price_per_night, 0),
                'capacity'        => $r->max_guests . ' guests',
                'available'       => $r->is_available ? 'Yes' : 'No',
                'link'            => $r->hotel?->slug ? "/hotel/{$r->hotel->slug}" : null,
            ])->toArray(),
        ];
    }

    private function toolGetUserBookings(array $args, $user): array
    {
        if (!$user) {
            return ['authenticated' => false, 'message' => 'User is not logged in.'];
        }

        $status = $args['status'] ?? 'all';

        $query = Booking::with(['room:id,name,type', 'hotel:id,name,city,slug'])
            ->where('user_id', $user->id)
            ->orderBy('check_in_date', 'desc');

        if ($status !== 'all') $query->where('status', $status);

        $bookings = $query->limit(10)->get();

        if ($bookings->isEmpty()) {
            return ['found' => false, 'message' => $status === 'all' ? 'No bookings yet.' : "No {$status} bookings."];
        }

        return [
            'found'    => true,
            'total'    => $bookings->count(),
            'bookings' => $bookings->map(fn($b) => [
                'id'        => $b->id,
                'hotel'     => $b->hotel?->name,
                'room'      => $b->room?->name,
                'room_type' => $b->room?->type,
                'check_in'  => $b->check_in_date?->format('Y-m-d'),
                'check_out' => $b->check_out_date?->format('Y-m-d'),
                'nights'    => $b->total_nights,
                'guests'    => $b->guests_count,
                'total'     => '$' . number_format($b->total_amount, 2),
                'status'    => $b->status,
                'link'      => $b->hotel?->slug ? "/hotel/{$b->hotel->slug}" : null,
            ])->toArray(),
        ];
    }
    private function toolCompareHotels(array $args): array
    {
        $names = $args['hotel_names'] ?? [];
        if (empty($names)) {
            return ['error' => 'No hotel names provided for comparison.'];
        }

        $hotels = Hotel::where(function ($q) use ($names) {
                foreach ($names as $name) {
                    $q->orWhere('name', 'like', "%{$name}%")
                      ->orWhere('slug', 'like', "%{$name}%");
                }
            })
            ->limit(4)
            ->get();

        if ($hotels->isEmpty()) {
            return ['found' => false, 'message' => 'Could not find any of the specified hotels to compare.'];
        }

        return [
            'found' => true,
            'hotels' => $hotels->map(fn($h) => [
                'name'               => $h->name,
                'location'           => "{$h->city}, {$h->country}",
                'rating'             => $h->rating,
                'price_per_night'    => '$' . number_format($h->price_per_night, 0),
                'type'               => $h->type,
                'description'        => \Illuminate\Support\Str::limit($h->description, 150),
                'amenities'          => is_array($h->amenities) ? array_slice($h->amenities, 0, 5) : [],
                'distance_center'    => $h->distance_from_center . ' km',
                'breakfast_included' => $h->has_breakfast_included ? 'Yes' : 'No',
                'spa_access'         => $h->has_spa_access ? 'Yes' : 'No',
                'link'               => "/hotel/{$h->slug}",
            ])->toArray(),
        ];
    }
}