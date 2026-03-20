<?php

namespace App\Observers;

use App\Models\Advertisement;
use App\Models\User;
use App\Notifications\NewAdvertisementNotification;
use Illuminate\Support\Facades\Notification;

class AdvertisementObserver
{
    /**
     * Handle the Advertisement "created" event.
     */
    public function created(Advertisement $advertisement): void
    {
        // Only notify if it's currently active (not future-dated)
        if ($advertisement->starts_at <= now() && $advertisement->ends_at >= now() && $advertisement->is_active) {
            $this->notifyAllUsers($advertisement);
        }
    }

    /**
     * Handle the Advertisement "updated" event.
     */
    public function updated(Advertisement $advertisement): void
    {
        // If the advertisement was just activated, notify users
        if (($advertisement->isDirty('is_active') && $advertisement->is_active) 
            || ($advertisement->isDirty('starts_at') && $advertisement->starts_at <= now() && $advertisement->is_active)) {
            
            // Check if it's within valid date range
            if ($advertisement->ends_at >= now()) {
                $this->notifyAllUsers($advertisement);
            }
        }
    }

    protected function notifyAllUsers(Advertisement $advertisement): void
    {
        // We notify all users for a general advertisement
        // For performance in larger systems, this should be a queued job
        $users = User::all();
        
        if ($users->isNotEmpty()) {
            try {
                Notification::send($users, new NewAdvertisementNotification($advertisement));
            } catch (\Exception $e) {
                \Log::error('AdvertisementObserver: Notification failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
