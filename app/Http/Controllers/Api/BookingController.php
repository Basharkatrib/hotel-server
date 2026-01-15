<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
            'guests_count' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $room = Room::find($request->room_id);
        
        // Check if guests count exceeds room capacity
        if ($request->has('guests_count') && $request->guests_count > $room->max_guests) {
            return $this->error([
                'This room can accommodate a maximum of ' . $room->max_guests . ' guests.'
            ], 422);
        }
        
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
        $user = $request->user();
        $query = Booking::with(['room', 'hotel', 'payment']);

        // Filter bookings based on user role
        if ($user->isAdmin()) {
            // Admin can see all bookings
            // No filter needed
        } elseif ($user->isHotelOwner()) {
            // Hotel owner can see bookings for their hotels only
            $query->whereHas('hotel', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } else {
            // Regular user can see only their own bookings
            $query->where('user_id', $user->id);
        }

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
        // Check authorization
        if (Gate::denies('create', Booking::class)) {
            return $this->error(['You do not have permission to create bookings.'], 403);
        }

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
        $booking = Booking::with(['room', 'hotel', 'payment'])->find($id);

        if (!$booking) {
            return $this->error(['Booking not found.'], 404);
        }

        // Check authorization
        if (Gate::denies('view', $booking)) {
            return $this->error(['You do not have permission to view this booking.'], 403);
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
        $booking = Booking::find($id);

        if (!$booking) {
            return $this->error(['Booking not found.'], 404);
        }

        // Check authorization
        if (Gate::denies('cancel', $booking)) {
            return $this->error(['You do not have permission to cancel this booking.'], 403);
        }

        if (!$booking->canBeCancelled()) {
            return $this->error(['This booking cannot be cancelled.'], 400);
        }

        $booking->update(['status' => 'cancelled']);

        // Check for refund (50% if cancelled 7+ days before check-in)
        if ($booking->payment && $booking->payment->status === 'succeeded') {
            $checkInDate = Carbon::parse($booking->check_in_date);
            $daysUntilCheckIn = now()->diffInDays($checkInDate, false);

            if ($daysUntilCheckIn >= 7) {
                // 50% Refund logic
                $refundAmount = $booking->payment->amount * 0.5;
                
                try {
                    \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                    
                    $refund = \Stripe\Refund::create([
                        'payment_intent' => $booking->payment->stripe_payment_intent_id,
                        'amount' => (int) ($refundAmount * 100), // convert to cents
                    ]);

                    $booking->payment->update([
                        'status' => 'refunded', // Or 'partially_refunded' if you prefer
                        'refunded_amount' => $refundAmount,
                        'stripe_refund_id' => $refund->id,
                    ]);

                    return $this->success(
                        ['booking' => $booking->fresh(['payment'])],
                        ["Booking cancelled. A 50% refund ($refundAmount) has been processed because the cancellation was made $daysUntilCheckIn days before check-in."]
                    );
                } catch (\Exception $e) {
                    Log::error("Refund failed for booking {$booking->id}: " . $e->getMessage());
                    return $this->error(['Cancellation succeeded, but automatic refund failed. Our staff will process it manually.'], 500);
                }
            } else {
                // No refund (less than 7 days)
                return $this->success(
                    ['booking' => $booking->fresh()],
                    ['Booking cancelled. No refund is available because the cancellation was made less than 7 days before check-in.']
                );
            }
        }

        return $this->success(
            ['booking' => $booking->fresh()],
            ['Booking cancelled successfully.']
        );
    }
}
