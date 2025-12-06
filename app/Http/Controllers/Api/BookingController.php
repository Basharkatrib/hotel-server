<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BookingController extends Controller
{
    use ApiResponse;

    /**
     * Check room availability for given dates.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'room_id' => ['required', 'exists:rooms,id'],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $room = Room::find($request->room_id);
        $isAvailable = $room->isAvailableForDates(
            $request->check_in_date,
            $request->check_out_date
        );

        if (!$isAvailable) {
            // Get conflicting bookings
            $conflictingBookings = $room->bookings()
                ->whereIn('status', ['pending', 'confirmed'])
                ->where(function ($query) use ($request) {
                    $query->where('check_in_date', '<', $request->check_out_date)
                          ->where('check_out_date', '>', $request->check_in_date);
                })
                ->get(['check_in_date', 'check_out_date']);

            return $this->success([
                'available' => false,
                'conflicting_dates' => $conflictingBookings,
            ], ['Room is not available for the selected dates.']);
        }

        // Calculate pricing
        $checkIn = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $nights = $checkIn->diffInDays($checkOut);
        $subtotal = $room->price_per_night * $nights;
        $serviceFee = $subtotal * 0.028;
        $taxes = $subtotal * 0.0164;
        $total = $subtotal + $serviceFee + $taxes;

        return $this->success([
            'available' => true,
            'pricing' => [
                'nights' => $nights,
                'price_per_night' => $room->price_per_night,
                'subtotal' => round($subtotal, 2),
                'service_fee' => round($serviceFee, 2),
                'taxes' => round($taxes, 2),
                'total' => round($total, 2),
            ],
        ], ['Room is available for the selected dates.']);
    }

    /**
     * Display a listing of user's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['room', 'hotel', 'payment'])
            ->where('user_id', $request->user()->id);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'upcoming') {
                $query->upcoming();
            } elseif ($request->status === 'past') {
                $query->past();
            } else {
                $query->where('status', $request->status);
            }
        }

        // Sort by check-in date
        $query->orderBy('check_in_date', 'desc');

        $bookings = $query->paginate($request->get('per_page', 15));

        return $this->success([
            'bookings' => $bookings->items(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ], ['Bookings retrieved successfully.']);
    }

    /**
     * Store a newly created booking.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'room_id' => ['required', 'exists:rooms,id'],
            'hotel_id' => ['required', 'exists:hotels,id'],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:20'],
            'guests_count' => ['required', 'integer', 'min:1'],
            'rooms_count' => ['integer', 'min:1'],
            'guests_details' => ['nullable', 'array'],
            'guests_details.*.name' => ['required_with:guests_details', 'string', 'max:255'],
            'guests_details.*.email' => ['required_with:guests_details', 'email', 'max:255'],
            'guests_details.*.phone' => ['required_with:guests_details', 'string', 'max:20'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        // Check availability again
        $room = Room::find($request->room_id);
        if (!$room->isAvailableForDates($request->check_in_date, $request->check_out_date)) {
            return $this->error(['Room is not available for the selected dates.'], 400);
        }

        // Check guest capacity
        if ($request->guests_count > $room->max_guests) {
            return $this->error(["This room can accommodate a maximum of {$room->max_guests} guests."], 400);
        }

        // Calculate dates and pricing
        $checkIn = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $nights = $checkIn->diffInDays($checkOut);

        $booking = new Booking([
            'user_id' => $request->user()->id,
            'room_id' => $request->room_id,
            'hotel_id' => $request->hotel_id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'total_nights' => $nights,
            'guest_name' => $request->guest_name,
            'guest_email' => $request->guest_email,
            'guest_phone' => $request->guest_phone,
            'guests_count' => $request->guests_count,
            'rooms_count' => $request->get('rooms_count', 1),
            'guests_details' => $request->guests_details,
            'price_per_night' => $room->price_per_night,
            'special_requests' => $request->special_requests,
        ]);

        $booking->calculateTotal();
        $booking->save();

        // Mark room as unavailable for these dates
        // Note: We're using the booking status to track availability
        // Room will be unavailable while booking is pending or confirmed

        return $this->success(
            ['booking' => $booking->load(['room', 'hotel'])],
            ['Booking created successfully.'],
            201
        );
    }

    /**
     * Display the specified booking.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $booking = Booking::with(['room', 'hotel', 'payment'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return $this->error(['Booking not found.'], 404);
        }

        return $this->success(
            ['booking' => $booking],
            ['Booking retrieved successfully.']
        );
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return $this->error(['Booking not found.'], 404);
        }

        if (!$booking->canBeCancelled()) {
            return $this->error(['This booking cannot be cancelled.'], 400);
        }

        $booking->update(['status' => 'cancelled']);

        // If payment exists, mark it for refund
        if ($booking->payment && $booking->payment->status === 'succeeded') {
            $booking->payment->markAsRefunded();
        }

        return $this->success(
            ['booking' => $booking->fresh()],
            ['Booking cancelled successfully.']
        );
    }
}
