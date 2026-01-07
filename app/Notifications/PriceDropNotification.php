<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        return ['database'];
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
}
