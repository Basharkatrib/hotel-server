<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class BookingConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $payment;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, $payment)
    {
        $this->booking = $booking->load(['room', 'hotel', 'user']);
        $this->payment = $payment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Booking Confirmation - ' . $this->booking->hotel->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmation',
            with: [
                'bookingNumber' => $this->booking->booking_number,
                'hotelName' => $this->booking->hotel->name,
                'roomName' => $this->booking->room->name,
                'checkIn' => $this->booking->check_in_date,
                'checkOut' => $this->booking->check_out_date,
                'totalAmount' => $this->payment->amount,
                'cardLast4' => $this->payment->card_last4,
                'userName' => $this->booking->user->name,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
