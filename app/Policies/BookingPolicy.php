<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine if the user can view any bookings.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view bookings (filtered by role in controller)
        return true;
    }

    /**
     * Determine if the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        // Admin can view any booking
        if ($user->isAdmin()) {
            return true;
        }

        // Hotel owner can view bookings for their hotels
        if ($user->isHotelOwner() && $booking->isOwnedByHotelOwner($user->id)) {
            return true;
        }

        // User can view their own bookings
        return $booking->isOwnedBy($user->id);
    }

    /**
     * Determine if the user can create bookings.
     */
    public function create(User $user): bool
    {
        // Only regular users can create bookings
        return $user->isRegularUser();
    }

    /**
     * Determine if the user can update the booking.
     */
    public function update(User $user, Booking $booking): bool
    {
        // Only admin can update bookings
        return $user->isAdmin();
    }

    /**
     * Determine if the user can cancel the booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        // Admin can cancel any booking
        if ($user->isAdmin()) {
            return true;
        }

        // Hotel owner can cancel bookings for their hotels
        if ($user->isHotelOwner() && $booking->isOwnedByHotelOwner($user->id)) {
            return true;
        }

        // User can cancel their own bookings
        return $booking->isOwnedBy($user->id);
    }
}



