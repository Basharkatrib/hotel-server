<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Http\Requests\CheckAvailabilityRequest;
use App\Http\Requests\StoreBookingRequest;
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

    protected $bookingService;
    protected $paymentService;

    public function __construct(BookingService $bookingService, PaymentService $paymentService)
    {
        $this->bookingService = $bookingService;
        $this->paymentService = $paymentService;
    }

    /**
     * Check room availability for given dates.
     */
    public function checkAvailability(CheckAvailabilityRequest $request): JsonResponse
    {
        $room = Room::findOrFail($request->room_id);
        
        // Check if guests count exceeds room capacity
        if ($request->has('guests_count') && $request->guests_count > $room->max_guests) {
            return $this->error([
                'This room can accommodate a maximum of ' . $room->max_guests . ' guests.'
            ], 422);
        }
        
        $availability = $this->bookingService->getAvailability(
            $room,
            $request->check_in_date,
            $request->check_out_date
        );

        if (!$availability['available']) {
            return $this->success([
                'available' => false,
                'conflicting_dates' => $availability['conflicting_dates'],
            ], ['Room is not available for the selected dates.']);
        }

        $pricing = $this->bookingService->calculatePricing(
            $room,
            $request->check_in_date,
            $request->check_out_date
        );

        return $this->success([
            'available' => true,
            'pricing' => $pricing,
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
        } elseif ($user->isHotelOwner()) {
            $query->whereHas('hotel', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } else {
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
    public function store(StoreBookingRequest $request): JsonResponse
    {
        // Check authorization
        if (Gate::denies('create', Booking::class)) {
            return $this->error(['You do not have permission to create bookings.'], 403);
        }

        $room = Room::findOrFail($request->room_id);
        
        // Re-check capacity & availability
        if ($request->guests_count > $room->max_guests) {
            return $this->error(["This room can accommodate a maximum of {$room->max_guests} guests."], 400);
        }

        if (!$room->isAvailableForDates($request->check_in_date, $request->check_out_date)) {
            return $this->error(['Room is not available for the selected dates.'], 400);
        }

        $booking = $this->bookingService->createBooking($request->validated(), $request->user());

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

        if (Gate::denies('cancel', $booking)) {
            return $this->error(['You do not have permission to cancel this booking.'], 403);
        }

        if (!$booking->canBeCancelled()) {
            return $this->error(['This booking cannot be cancelled.'], 400);
        }

        $result = $this->bookingService->cancelBooking($booking);

        if (!$result['success']) {
            return $this->error([$result['message']], 500);
        }

        return $this->success(
            ['booking' => $booking->fresh(['payment'])],
            [$result['message']]
        );
    }
}

