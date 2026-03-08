<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class PriceDropNotification extends Notification
{
    use Queueable;

    protected $room;
    protected $oldPrice;

    /**
     * Create a new notification instance.
     */
    public function __construct($room, $oldPrice)
    {
        $this->room = $room;
        $this->oldPrice = $oldPrice;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database']; // Always store in DB

        // Only send FCM if user has a token
        if ($notifiable->fcm_token) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'room_id' => $this->room->id,
            'room_name' => $this->room->name,
            'hotel_name' => $this->room->hotel->name,
            'old_price' => $this->oldPrice,
            'new_price' => $this->room->price_per_night,
            'image' => $this->room->images[0] ?? null,
            'message' => "Good news! The price for {$this->room->name} at {$this->room->hotel->name} has dropped from \${$this->oldPrice} to \${$this->room->price_per_night}!",
        ];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm($notifiable): FcmMessage
    {
        return (new FcmMessage())
            ->data([
                'title' => 'Price Drop Alert!',
                'body' => "The price for {$this->room->name} at {$this->room->hotel->name} has dropped to \${$this->room->price_per_night}!",
                'room_id' => (string)$this->room->id,
                'type' => 'price_drop',
            ]);
    }
}
