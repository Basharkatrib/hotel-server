<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use App\Models\Advertisement;

class NewAdvertisementNotification extends Notification
{
    use Queueable;

    protected $advertisement;

    /**
     * Create a new notification instance.
     */
    public function __construct(Advertisement $advertisement)
    {
        $this->advertisement = $advertisement;
        $this->advertisement->load('hotel');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Only send FCM if user has a token
        if ($notifiable->fcm_token) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $discount = $this->advertisement->discount_type === 'percentage' 
            ? "{$this->advertisement->discount_value}% OFF" 
            : "$\${$this->advertisement->discount_value} OFF";

        return [
            'advertisement_id' => $this->advertisement->id,
            'hotel_name' => $this->advertisement->hotel->name,
            'hotel_slug' => $this->advertisement->hotel->slug,
            'title' => "Special Offer from {$this->advertisement->hotel->name}!",
            'message' => "{$this->advertisement->title}: Save {$discount}! Valid until {$this->advertisement->ends_at->format('Y-m-d')}.",
            'type' => 'advertisement',
        ];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm($notifiable): FcmMessage
    {
        $discount = $this->advertisement->discount_type === 'percentage' 
            ? "{$this->advertisement->discount_value}% OFF" 
            : "$\${$this->advertisement->discount_value} OFF";

        return (new FcmMessage())
            ->data([
                'title' => "Special Offer from {$this->advertisement->hotel->name}!",
                'body' => "{$this->advertisement->title}: Save {$discount}!",
                'hotel_slug' => $this->advertisement->hotel->slug,
                'type' => 'advertisement',
            ]);
    }
}
