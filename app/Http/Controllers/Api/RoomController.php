<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of rooms with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Room::with('hotel:id,name,city,address');

        // Filter by hotel
        if ($request->has('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        // Store check-in and check-out dates for later use
        $checkIn = $request->check_in_date ?? null;
        $checkOut = $request->check_out_date ?? null;

        // Filter by type
        if ($request->has('type') && $request->type !== '' && $request->type !== null) {
            $query->where('type', $request->type);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->min_price !== '' && $request->min_price !== null) {
            $query->where('price_per_night', '>=', $request->min_price);
        }

        if ($request->has('max_price') && $request->max_price !== '' && $request->max_price !== null) {
            $query->where('price_per_night', '<=', $request->max_price);
        }

        // Filter by number of beds (total beds)
        if ($request->has('min_beds')) {
            $minBeds = (int) $request->min_beds;
            $query->whereRaw('(single_beds + double_beds + king_beds + queen_beds) >= ?', [$minBeds]);
        }

        if ($request->has('max_beds')) {
            $maxBeds = (int) $request->max_beds;
            $query->whereRaw('(single_beds + double_beds + king_beds + queen_beds) <= ?', [$maxBeds]);
        }

        // Filter by specific bed type
        if ($request->has('single_beds')) {
            $query->where('single_beds', '>=', $request->single_beds);
        }

        if ($request->has('double_beds')) {
            $query->where('double_beds', '>=', $request->double_beds);
        }

        if ($request->has('king_beds')) {
            $query->where('king_beds', '>=', $request->king_beds);
        }

        // Filter by max guests
        if ($request->has('max_guests') && $request->max_guests !== '' && $request->max_guests !== null) {
            $query->where('max_guests', '>=', $request->max_guests);
        }

        // Filter by features
        if ($request->has('has_breakfast') && ($request->has_breakfast === true || $request->has_breakfast === 'true' || $request->has_breakfast === '1')) {
            $query->where('has_breakfast', true);
        }

        if ($request->has('has_wifi') && ($request->has_wifi === true || $request->has_wifi === 'true' || $request->has_wifi === '1')) {
            $query->where('has_wifi', true);
        }

        if ($request->has('has_ac') && ($request->has_ac === true || $request->has_ac === 'true' || $request->has_ac === '1')) {
            $query->where('has_ac', true);
        }

        if ($request->has('has_balcony') && ($request->has_balcony === true || $request->has_balcony === 'true' || $request->has_balcony === '1')) {
            $query->where('has_balcony', true);
        }

        // Filter by view
        if ($request->has('view') && $request->view !== '' && $request->view !== null) {
            $query->where('view', $request->view);
        }

        // Filter by availability
        if ($request->has('available_only') && $request->available_only) {
            $query->where('is_available', true);
        }

        // Only active rooms by default
        if (!$request->has('include_inactive')) {
            $query->where('is_active', true);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort order
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        // Validate and apply sorting
        if (in_array($sortBy, ['price_per_night', 'rating', 'created_at', 'max_guests'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            // Default sorting if invalid sort_by
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $rooms = $query->paginate($perPage);

        // Add availability information for each room if dates are provided
        $roomsWithAvailability = collect($rooms->items())->map(function ($room) use ($checkIn, $checkOut) {
            $roomArray = $room->toArray();
            
            // Check if room is available for the selected dates
            if ($checkIn && $checkOut) {
                try {
                    $hasConflict = $room->bookings()
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->where(function ($query) use ($checkIn, $checkOut) {
                            $query->where('check_in_date', '<', $checkOut)
                                  ->where('check_out_date', '>', $checkIn);
                        })
                        ->exists();
                    
                    $roomArray['is_available_for_dates'] = !$hasConflict;
                    
                    // If there's a conflict, get the booking details
                    if ($hasConflict) {
                        $conflictingBooking = $room->bookings()
                            ->whereIn('status', ['pending', 'confirmed'])
                            ->where(function ($query) use ($checkIn, $checkOut) {
                                $query->where('check_in_date', '<', $checkOut)
                                      ->where('check_out_date', '>', $checkIn);
                            })
                            ->first(['check_in_date', 'check_out_date']);
                        
                        if ($conflictingBooking) {
                            $roomArray['booked_dates'] = [
                                'check_in' => $conflictingBooking->check_in_date,
                                'check_out' => $conflictingBooking->check_out_date,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // If there's an error checking availability, default to room's general availability
                    $roomArray['is_available_for_dates'] = $room->is_available;
                }
            } else {
                $roomArray['is_available_for_dates'] = $room->is_available;
            }
            
            return $roomArray;
        });

        return $this->success([
            'rooms' => $roomsWithAvailability->toArray(),
            'pagination' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
                'from' => $rooms->firstItem(),
                'to' => $rooms->lastItem(),
            ]
        ], ['Rooms retrieved successfully.']);
    }

    /**
     * Store a newly created room.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'hotel_id' => ['required', 'exists:hotels,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:single,double,suite,deluxe,penthouse'],
            'size' => ['nullable', 'integer', 'min:1'],
            'max_guests' => ['required', 'integer', 'min:1'],
            'single_beds' => ['integer', 'min:0'],
            'double_beds' => ['integer', 'min:0'],
            'king_beds' => ['integer', 'min:0'],
            'queen_beds' => ['integer', 'min:0'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['integer', 'min:0', 'max:100'],
            'is_available' => ['boolean'],
            'has_breakfast' => ['boolean'],
            'has_wifi' => ['boolean'],
            'has_ac' => ['boolean'],
            'has_tv' => ['boolean'],
            'has_minibar' => ['boolean'],
            'has_safe' => ['boolean'],
            'has_balcony' => ['boolean'],
            'has_bathtub' => ['boolean'],
            'has_shower' => ['boolean'],
            'no_smoking' => ['boolean'],
            'view' => ['in:city,sea,mountain,garden,pool,none'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $room = Room::create($request->all());

        return $this->success(
            ['room' => $room->load('hotel')],
            ['Room created successfully.'],
            201
        );
    }

    /**
     * Display the specified room.
     */
    public function show(int $id): JsonResponse
    {
        $room = Room::with('hotel')->find($id);

        if (!$room) {
            return $this->error(['Room not found.'], 404);
        }

        return $this->success(
            ['room' => $room],
            ['Room retrieved successfully.']
        );
    }

    /**
     * Update the specified room.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $room = Room::find($id);

        if (!$room) {
            return $this->error(['Room not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'hotel_id' => ['exists:hotels,id'],
            'name' => ['string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['in:single,double,suite,deluxe,penthouse'],
            'size' => ['nullable', 'integer', 'min:1'],
            'max_guests' => ['integer', 'min:1'],
            'single_beds' => ['integer', 'min:0'],
            'double_beds' => ['integer', 'min:0'],
            'king_beds' => ['integer', 'min:0'],
            'queen_beds' => ['integer', 'min:0'],
            'price_per_night' => ['numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['integer', 'min:0', 'max:100'],
            'is_available' => ['boolean'],
            'has_breakfast' => ['boolean'],
            'has_wifi' => ['boolean'],
            'has_ac' => ['boolean'],
            'has_tv' => ['boolean'],
            'has_minibar' => ['boolean'],
            'has_safe' => ['boolean'],
            'has_balcony' => ['boolean'],
            'has_bathtub' => ['boolean'],
            'has_shower' => ['boolean'],
            'no_smoking' => ['boolean'],
            'view' => ['in:city,sea,mountain,garden,pool,none'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $room->update($request->all());

        return $this->success(
            ['room' => $room->fresh()->load('hotel')],
            ['Room updated successfully.']
        );
    }

    /**
     * Remove the specified room.
     */
    public function destroy(int $id): JsonResponse
    {
        $room = Room::find($id);

        if (!$room) {
            return $this->error(['Room not found.'], 404);
        }

        $room->delete();

        return $this->success(
            null,
            ['Room deleted successfully.']
        );
    }
}
