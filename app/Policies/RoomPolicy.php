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
        // Admin, hotel owner and authorized staff can create rooms
        if ($user->isAdmin() || $user->isHotelOwner()) {
            return true;
        }

        if ($user->isHotelStaff()) {
            // Since create doesn't have a room instance, we check if they have manage_rooms permission 
            // at least for one hotel they are assigned to. 
            // Filament will filter the hotel list in the form.
            return $user->hotelStaff()->whereHas('permissions', fn($q) => $q->where('name', 'manage_rooms'))->exists();
        }

        return false;
    }

    /**
     * Determine if the user can update the room.
     */
    public function update(User $user, Room $room): bool
    {
        if ($user->isAdmin()) return true;

        if ($user->isHotelOwner() && $room->isOwnedBy($user->id)) {
            return true;
        }

        if ($user->isHotelStaff()) {
            return $user->hasStaffPermission('manage_rooms', $room->hotel_id);
        }

        return false;
    }

    /**
     * Determine if the user can delete the room.
     */
    public function delete(User $user, Room $room): bool
    {
        if ($user->isAdmin()) return true;

        if ($user->isHotelOwner() && $room->isOwnedBy($user->id)) {
            return true;
        }

        if ($user->isHotelStaff()) {
            return $user->hasStaffPermission('manage_rooms', $room->hotel_id);
        }

        return false;
    }
}



