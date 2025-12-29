<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    /**
     * Determine if the user can view any rooms.
     */
    public function viewAny(User $user): bool
    {
        // Everyone can view rooms
        return true;
    }

    /**
     * Determine if the user can view the room.
     */
    public function view(User $user, Room $room): bool
    {
        // Everyone can view rooms
        return true;
    }

    /**
     * Determine if the user can create rooms.
     */
    public function create(User $user): bool
    {
        // Admin and hotel owner can create rooms
        return $user->isAdmin() || $user->isHotelOwner();
    }

    /**
     * Determine if the user can update the room.
     */
    public function update(User $user, Room $room): bool
    {
        // Admin can update any room, hotel owner can update only rooms in their hotels
        return $user->isAdmin() || ($user->isHotelOwner() && $room->isOwnedBy($user->id));
    }

    /**
     * Determine if the user can delete the room.
     */
    public function delete(User $user, Room $room): bool
    {
        // Admin can delete any room, hotel owner can delete only rooms in their hotels
        return $user->isAdmin() || ($user->isHotelOwner() && $room->isOwnedBy($user->id));
    }
}

