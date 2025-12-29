<?php

namespace App\Policies;

use App\Models\Hotel;
use App\Models\User;

class HotelPolicy
{
    /**
     * Determine if the user can view any hotels.
     */
    public function viewAny(User $user): bool
    {
        // Everyone can view hotels
        return true;
    }

    /**
     * Determine if the user can view the hotel.
     */
    public function view(User $user, Hotel $hotel): bool
    {
        // Everyone can view hotels
        return true;
    }

    /**
     * Determine if the user can create hotels.
     */
    public function create(User $user): bool
    {
        // Admin or hotel owner can create hotels
        return $user->isAdmin() || $user->isHotelOwner();
    }

    /**
     * Determine if the user can update the hotel.
     */
    public function update(User $user, Hotel $hotel): bool
    {
        // Admin can update any hotel, hotel owner can update only their hotels
        return $user->isAdmin() || ($user->isHotelOwner() && $hotel->isOwnedBy($user->id));
    }

    /**
     * Determine if the user can delete the hotel.
     */
    public function delete(User $user, Hotel $hotel): bool
    {
        // Admin can delete any hotel, hotel owner can delete only their hotels
        return $user->isAdmin() || ($user->isHotelOwner() && $hotel->isOwnedBy($user->id));
    }
}

