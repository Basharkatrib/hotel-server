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
    private string $model;

    public function __construct()
    {
        $this->apiKey  = env('GROQ_API_KEY', '');
        $this->baseUrl = env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1');
        $this->model   = env('GROQ_MODEL', 'llama-3.3-70b-versatile');
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

        $user     = auth('sanctum')->user();
        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($user)],
            ...$request->messages,
        ];

        try {
            $response     = $this->callGroq([
                'model'       => $this->model,
                'messages'    => $messages,
                'tools'       => $this->buildTools(),
                'tool_choice' => 'auto',
                'max_tokens'  => 1024,
            ]);

            $choice       = $response['choices'][0];
            $finishReason = $choice['finish_reason'] ?? '';

            $iterations = 0;
            while ($finishReason === 'tool_calls' && $iterations < 3) {
                $iterations++;
                $messages[] = $choice['message'];

                foreach ($choice['message']['tool_calls'] ?? [] as $toolCall) {
                    $toolName = $toolCall['function']['name'];
                    $toolArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];

                    if (isset($toolArgs['limit']))    $toolArgs['limit']    = (int) $toolArgs['limit'];
                    if (isset($toolArgs['hotel_id'])) $toolArgs['hotel_id'] = (int) $toolArgs['hotel_id'];

                    // Tool errors are caught inside executeToolCall — never throws
                    $toolResult = $this->executeToolCall($toolName, $toolArgs, $user);

                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content'      => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
                    ];
                }

                $response     = $this->callGroq([
                    'model'      => $this->model,
                    'messages'   => $messages,
                    'max_tokens' => 1024,
                ]);

                $choice       = $response['choices'][0];
                $finishReason = $choice['finish_reason'] ?? '';
            }

            $message = $choice['message']['content'] ?? null;

            // Fallback if model returns empty content
            if (empty(trim($message ?? ''))) {
                $message = $this->fallbackMessage($request->messages);
            }

            return response()->json([
                'status'  => 'success',
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            Log::error('ChatController error', ['error' => $e->getMessage()]);

            // Always return 200 with a friendly message — never expose 500 to frontend
            return response()->json([
                'status'  => 'success',
                'message' => $this->groqFallbackMessage(),
            ]);
        }
    }

    // =========================================================================
    // Groq HTTP helper
    // =========================================================================
    private function callGroq(array $payload): array
    {
        $response = Http::timeout(60)
            ->retry(2, 1500)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post("{$this->baseUrl}/chat/completions", $payload);

        if ($response->failed()) {
            throw new \Exception('Groq API error: ' . $response->body());
        }

        return $response->json();
    }

    // =========================================================================
    // Friendly fallback messages (no 500 ever shown to user)
    // =========================================================================
    private function groqFallbackMessage(): string
    {
        $messages = [
            "I'm having a small hiccup right now. Please try again in a moment! 😊",
            "عذراً، واجهت مشكلة مؤقتة. يرجى المحاولة مجدداً بعد لحظات 😊",
        ];

        // Try to detect last user language from request — default to English
        return $messages[0];
    }

    private function fallbackMessage(array $userMessages): string
    {
        $lastContent = collect($userMessages)->last()['content'] ?? '';
        $isArabic    = preg_match('/\p{Arabic}/u', $lastContent);

        return $isArabic
            ? 'عذراً، لم أتمكن من معالجة طلبك. هل يمكنك إعادة صياغة السؤال؟ 😊'
            : "I couldn't process that request. Could you rephrase your question? 😊";
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
- Detect the language of every user message.
- Arabic message  → reply ENTIRELY in Arabic. No English words.
- English message → reply ENTIRELY in English. No Arabic words.
- Greetings like "مرحبا" or "hello" → reply in that same language.
- NEVER mix the two languages in a single reply.

=== UNKNOWN QUESTIONS ===
If the user asks something you don't know or that is outside your scope:
- Do NOT return an error.
- Politely say you don't have that information in the same language as the user.
- Suggest they contact support at support@vayka.com or visit the platform.
- Example Arabic: "عذراً، لا تتوفر لديّ هذه المعلومات حالياً. يمكنك التواصل مع الدعم على support@vayka.com"
- Example English: "I'm sorry, I don't have that information right now. Please contact support@vayka.com"

=== TOOLS — ALWAYS USE BEFORE ANSWERING ===
Never answer from memory about hotels, rooms, or bookings. Always call the correct tool first.

| Tool               | Trigger phrases (examples)                                              |
|--------------------|-------------------------------------------------------------------------|
| list_all_hotels    | "الفنادق", "كل الفنادق", "قائمة الفنادق", "show hotels", "all hotels"  |
| search_hotels      | city name mentioned: "دبي", "إسبانيا", "Dubai", "Spain", "Amman"       |
| get_room_details   | "الغرف", "أسعار الغرف", "rooms", "room prices", "available rooms"      |
| get_user_bookings  | "حجوزاتي", "my bookings", "reservations", "هل لدي حجز"                 |

=== RESPONSE FORMAT ===

**Hotels:**
1. [Name] — 📍 [City, Country] | ⭐ [rating] | 💰 [price]/night | 🚪 [N] rooms available
   🔗 /hotel/[slug]

**Rooms:**
1. [Room name] — 🏨 [Hotel] | 🛏 [type] | 💰 [price]/night | 👥 [capacity] guests | [✅ Available / ❌ Not available]

**Bookings:**
1. 🏨 [Hotel] — [Room]
   📅 [check_in] → [check_out] | 🌙 [nights] nights | 👥 [guests] guests | 💰 [total]
   Status: ✅ Confirmed / ⏳ Pending / ❌ Cancelled / 🏁 Completed

**General rules:**
- Be warm, concise, professional — like a 5-star concierge.
- If no results found: say so clearly and suggest what the user can do next.
- If user asks about bookings but is NOT authenticated: ask them to log in.
- Never fabricate any data.
- For support: support@vayka.com

=== ABOUT VAYKA ===
Premium hotel booking platform — hotels, villas, resorts worldwide.
Features: search by city/dates/guests, Favorites, secure payments via Stripe.
EOT;
    }

    // =========================================================================
    // Tool definitions
    // =========================================================================
    private function buildTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'list_all_hotels',
                    'description' => 'List all hotels on the platform sorted by rating. Use when the user asks to see all hotels without specifying a city.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'limit' => ['type' => 'integer', 'description' => 'Max hotels to return (default: 20)'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'search_hotels',
                    'description' => 'Search hotels by city or country name.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'city'  => ['type' => 'string',  'description' => 'City or country name (e.g. "Dubai", "Spain")'],
                            'limit' => ['type' => 'integer', 'description' => 'Max results (default: 10)'],
                        ],
                        'required' => ['city'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_room_details',
                    'description' => 'Get room types, prices, capacity and availability.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'hotel_id'       => ['type' => 'integer', 'description' => 'Filter by hotel ID (optional)'],
                            'only_available' => ['type' => 'boolean', 'description' => 'Show only available rooms (default: false)'],
                            'limit'          => ['type' => 'integer', 'description' => 'Max rooms (default: 10)'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_user_bookings',
                    'description' => "Retrieve the current user's booking history.",
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'status' => [
                                'type'        => 'string',
                                'description' => 'Filter by status (default: all)',
                                'enum'        => ['all', 'pending', 'confirmed', 'cancelled', 'completed'],
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
        ];
    }

    // =========================================================================
    // Tool router — NEVER throws, always returns array
    // =========================================================================
    private function executeToolCall(string $name, array $args, $user): array
    {
        try {
            return match ($name) {
                'list_all_hotels'   => $this->toolListAllHotels($args),
                'search_hotels'     => $this->toolSearchHotels($args),
                'get_room_details'  => $this->toolGetRoomDetails($args),
                'get_user_bookings' => $this->toolGetUserBookings($args, $user),
                default             => ['error' => "Unknown tool: {$name}"],
            };
        } catch (\Exception $e) {
            Log::error("Tool [{$name}] failed", ['error' => $e->getMessage()]);
            return ['error' => "Tool [{$name}] encountered an error. Please try again."];
        }
    }

    // =========================================================================
    // Tool: List all hotels
    // =========================================================================
    private function toolListAllHotels(array $args): array
    {
        $limit = (int) ($args['limit'] ?? 20);

        $hotels = Hotel::select('id', 'name', 'city', 'country', 'price_per_night', 'rating', 'type', 'slug')
            ->withCount(['rooms as available_rooms' => fn($q) => $q->where('is_available', true)])
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();

        if ($hotels->isEmpty()) {
            return ['found' => false, 'message' => 'No hotels are currently listed on the platform.'];
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

    // =========================================================================
    // Tool: Search hotels by city/country
    // =========================================================================
    private function toolSearchHotels(array $args): array
    {
        $city  = trim($args['city'] ?? '');
        $limit = (int) ($args['limit'] ?? 10);

        if (empty($city)) {
            return $this->toolListAllHotels($args);
        }

        $hotels = Hotel::where(function ($q) use ($city) {
                $q->where('city', 'like', "%{$city}%")
                  ->orWhere('country', 'like', "%{$city}%")
                  ->orWhere('name', 'like', "%{$city}%");
            })
            ->select('id', 'name', 'city', 'country', 'price_per_night', 'rating', 'type', 'slug')
            ->withCount(['rooms as available_rooms' => fn($q) => $q->where('is_available', true)])
            ->orderBy('rating', 'desc')
            ->limit($limit)
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

    // =========================================================================
    // Tool: Room details & prices
    // =========================================================================
    private function toolGetRoomDetails(array $args): array
    {
        $hotelId       = isset($args['hotel_id']) ? (int) $args['hotel_id'] : null;
        $onlyAvailable = (bool) ($args['only_available'] ?? false);
        $limit         = (int) ($args['limit'] ?? 10);

        $query = Room::with('hotel:id,name,city,slug')
            ->where('is_active', true)
            ->select('id', 'hotel_id', 'name', 'type', 'price_per_night', 'max_guests', 'is_available');

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        if ($onlyAvailable) {
            $query->where('is_available', true);
        }

        $rooms = $query->orderBy('price_per_night')->limit($limit)->get();

        if ($rooms->isEmpty()) {
            return ['found' => false, 'message' => 'No rooms found matching your criteria.'];
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

    // =========================================================================
    // Tool: User bookings
    // =========================================================================
    private function toolGetUserBookings(array $args, $user): array
    {
        if (!$user) {
            return ['authenticated' => false, 'message' => 'User is not logged in.'];
        }

        $status = $args['status'] ?? 'all';

        $query = Booking::with(['room:id,name,type', 'hotel:id,name,city,slug'])
            ->where('user_id', $user->id)
            ->orderBy('check_in_date', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $bookings = $query->limit(10)->get();

        if ($bookings->isEmpty()) {
            $msg = $status === 'all'
                ? 'This user has no bookings yet.'
                : "No {$status} bookings found.";
            return ['found' => false, 'message' => $msg];
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
}