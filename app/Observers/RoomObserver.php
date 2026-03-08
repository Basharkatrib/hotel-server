<?php

namespace App\Observers;

use App\Models\Room;
use App\Models\User;
use App\Models\Hotel;
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
        \Log::info('RoomObserver: Room updated', ['room_id' => $room->id]);
        
        if ($room->isDirty('price_per_night')) {
            $oldPrice = $room->getOriginal('price_per_night');
            $newPrice = $room->price_per_night;
            
            \Log::info('RoomObserver: Price change detected', ['old' => $oldPrice, 'new' => $newPrice]);

            if ($newPrice < $oldPrice) {
                \Log::info('RoomObserver: Price dropped, notifying users...');
                $this->notifyInterestedUsers($room, $oldPrice);
            }
        }
    }

    protected function notifyInterestedUsers(Room $room, $oldPrice): void
    {
        $morphClass = $room->getMorphClass();
        \Log::info('RoomObserver: Looking for users who favorited', [
            'morph_class' => $morphClass,
            'room_id' => $room->id
        ]);

        // Find users who have favorited this room
        $userIds = Favorite::where('favoritable_type', $morphClass)
            ->where('favoritable_id', $room->id)
            ->pluck('user_id');

        \Log::info('RoomObserver: Found user IDs', ['ids' => $userIds->toArray()]);

        $users = User::whereIn('id', $userIds)
            ->get();
        
        \Log::info('RoomObserver: Found interested users', [
            'interested_user_count' => $users->count()
        ]);

        if ($users->isNotEmpty()) {
            try {
                // This will store in database for all these users AND send FCM for those who have a token
                \Illuminate\Support\Facades\Notification::send($users, new PriceDropNotification($room, $oldPrice));
                \Log::info('RoomObserver: Notifications dispatched successfully.');
            } catch (\Exception $e) {
                \Log::error('RoomObserver: Notification sending failed', ['error' => $e->getMessage()]);
            }
        } else {
            \Log::warning('RoomObserver: No users found to notify.');
        }
    }
}
