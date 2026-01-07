<?php

namespace App\Observers;

use App\Models\Room;
use App\Models\User;
use App\Models\Favorite;
use App\Notifications\PriceDropNotification;
use Illuminate\Support\Facades\Notification;

class RoomObserver
{
    /**
     * Handle the Room "updated" event.
     */
    public function updated(Room $room): void
    {
        // Check if price_per_night has changed and decreased
        if ($room->isDirty('price_per_night')) {
            $oldPrice = $room->getOriginal('price_per_night');
            $newPrice = $room->price_per_night;

            if ($newPrice < $oldPrice) {
                $this->notifyInterestedUsers($room, $oldPrice);
            }
        }
    }

    /**
     * Notify users who have this room or its hotel in their favorites.
     */
    protected function notifyInterestedUsers(Room $room, $oldPrice): void
    {
        // 1. Users who favorited this specific room
        $roomFavoriteUserIds = Favorite::where('favoritable_type', Room::class) // matches App\Models\Room
            ->where('favoritable_id', $room->id)
            ->pluck('user_id');

        // 2. Users who favorited the hotel this room belongs to
        $hotelFavoriteUserIds = Favorite::where('favoritable_type', Hotel::class) // matches App\Models\Hotel
            ->where('favoritable_id', $room->hotel_id)
            ->pluck('user_id');

        // Combine and get unique user IDs
        $userIds = $roomFavoriteUserIds->merge($hotelFavoriteUserIds)->unique();

        if ($userIds->isNotEmpty()) {
            $users = User::findMany($userIds);
            Notification::send($users, new PriceDropNotification($room, $oldPrice));
        }
    }
}
