<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    /**
     * Calculate pricing for a room stay.
     */
    public function calculatePricing(Room $room, string $checkIn, string $checkOut): array
    {
        $checkInDate = Carbon::parse($checkIn);
        $checkOutDate = Carbon::parse($checkOut);
        $nights = $checkInDate->diffInDays($checkOutDate);
        
        $subtotal = $room->price_per_night * $nights;
        $serviceFee = $subtotal * 0.028; // 2.8%
        $taxes = $subtotal * 0.0164; // 1.64%
        $total = $subtotal + $serviceFee + $taxes;

        return [
            'nights' => $nights,
            'price_per_night' => $room->price_per_night,
            'subtotal' => round($subtotal, 2),
            'service_fee' => round($serviceFee, 2),
            'taxes' => round($taxes, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Check availability and return conflicting dates if any.
     */
    public function getAvailability(Room $room, string $checkIn, string $checkOut): array
    {
        $isAvailable = $room->isAvailableForDates($checkIn, $checkOut);

        $conflictingDates = [];
        if (!$isAvailable) {
            $conflictingDates = $room->bookings()
                ->whereIn('status', ['pending', 'confirmed'])
                ->where(function ($query) use ($checkIn, $checkOut) {
                    $query->whereDate('check_in_date', '<=', $checkOut)
                          ->whereDate('check_out_date', '>=', $checkIn);
                })
                ->get(['check_in_date', 'check_out_date']);
        }

        return [
            'available' => $isAvailable,
            'conflicting_dates' => $conflictingDates,
        ];
    }

    /**
     * Create a new booking.
     */
    public function createBooking(iterable $data, User $user): Booking
    {
        return DB::transaction(function () use ($data, $user) {
            $room = Room::findOrFail($data['room_id']);
            
            $checkIn = Carbon::parse($data['check_in_date']);
            $checkOut = Carbon::parse($data['check_out_date']);
            $nights = $checkIn->diffInDays($checkOut);

            $booking = new Booking([
                'user_id' => $user->id,
                'room_id' => $data['room_id'],
                'hotel_id' => $data['hotel_id'],
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'total_nights' => $nights,
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'],
                'guest_phone' => $data['guest_phone'],
                'guests_count' => $data['guests_count'],
                'rooms_count' => $data['rooms_count'] ?? 1,
                'guests_details' => $data['guests_details'] ?? null,
                'price_per_night' => $room->price_per_night,
                'special_requests' => $data['special_requests'] ?? null,
            ]);

            $booking->calculateTotal();
            $booking->save();

            return $booking;
        });
    }

    /**
     * Cancel a booking and determine refund.
     */
    public function cancelBooking(Booking $booking): array
    {
        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);

            $refundStatus = [
                'success' => true,
                'refund_processed' => false,
                'refund_amount' => 0,
                'message' => 'Booking cancelled successfully.'
            ];

            if ($booking->payment && $booking->payment->status === 'succeeded') {
                $daysUntilCheckIn = now()->diffInDays($booking->check_in_date, false);

                if ($daysUntilCheckIn >= 7) {
                    $refundAmount = $booking->payment->amount * 0.5;
                    
                    try {
                        $paymentService = app(PaymentService::class);
                        $result = $paymentService->refund($booking->payment, 0.5);
                        
                        $refundStatus['refund_processed'] = true;
                        $refundStatus['refund_amount'] = $refundAmount;
                        $refundStatus['message'] = "Booking cancelled. A 50% refund ($refundAmount) has been processed.";
                    } catch (\Exception $e) {
                        Log::error("Refund failed for booking {$booking->id}: " . $e->getMessage());
                        $refundStatus['success'] = false;
                        $refundStatus['message'] = "Booking cancelled, but refund failed. Manual processing required.";
                    }
                } else {
                    $refundStatus['message'] = "Booking cancelled. No refund available (less than 7 days).";
                }
            }

            return $refundStatus;
        });
    }
}
