<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Stripe\PaymentIntent;
use Stripe\Refund;

class PaymentService
{
    public function __construct()
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create Stripe Payment Intent
     */
    public function createIntent(Booking $booking): array
    {
        // ✅ Ensure relations are already loaded (prevents N+1)
        $booking->loadMissing(['hotel:id,name', 'room:id,name']);

        // 1. Create or fetch payment record
        $payment = Payment::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'amount'         => $booking->total_amount,
                'currency'       => 'usd',
                'status'         => 'pending',
                'payment_method' => 'card',
            ]
        );

        // 2. Stripe Payment Intent (keep metadata light for performance)
        $paymentIntent = PaymentIntent::create([
            'amount'   => (int) round($booking->total_amount * 100),
            'currency' => 'usd',

            // ⚡ Keep description minimal
            'description' => "Booking #{$booking->id}",

            // ⚡ Only IDs (faster + safer + smaller payload)
            'metadata' => [
                'booking_id' => $booking->id,
                'hotel_id'   => $booking->hotel_id,
                'room_id'    => $booking->room_id,
            ],
        ]);

        // 3. Save Stripe intent id (only DB write after success)
        $payment->update([
            'stripe_payment_intent_id' => $paymentIntent->id,
        ]);

        return [
            'client_secret'     => $paymentIntent->client_secret,
            'payment_intent_id' => $paymentIntent->id,
            'amount'            => $booking->total_amount,
        ];
    }

    /**
     * Confirm payment after Stripe completion
     */
    public function confirmPayment(string $paymentIntentId): Payment
    {
        // ⚡ Retrieve only what we need
        $stripeIntent = PaymentIntent::retrieve($paymentIntentId);

        if ($stripeIntent->status !== 'succeeded') {
            throw new \Exception('Payment has not been completed yet.');
        }

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
            ->with(['booking:id,room_id,hotel_id,status']) // ⚡ lightweight load
            ->firstOrFail();

        if ($payment->status === 'succeeded') {
            return $payment;
        }

        $charge = $stripeIntent->charges->data[0] ?? null;

        $card = $charge?->payment_method_details?->card;

        // 1. Update payment
        $payment->update([
            'status'           => 'succeeded',
            'stripe_charge_id' => $charge?->id,
            'card_last4'       => $card?->last4,
            'card_brand'       => $card?->brand,
            'paid_at'          => now(),
        ]);

        // 2. Update booking status
        $payment->booking->update([
            'status' => 'confirmed',
        ]);

        Log::info("Payment {$payment->id} confirmed.");

        // 3. Send booking receipt email (similar to OTP sending method)
        try {
            $booking = Booking::with(['room', 'hotel', 'payment', 'user'])->find($payment->booking_id);
            if ($booking && $booking->user && $booking->user->email) {
                $pdf = Pdf::loadView('receipts.booking', compact('booking'));
                Mail::send(
                    'receipts.booking',
                    compact('booking'),
                    function ($message) use ($booking, $pdf) {
                        $message->to($booking->user->email)
                            ->subject("Your Booking Receipt - Booking #{$booking->id} - Vayka")
                            ->attachData($pdf->output(), "receipt-booking-{$booking->id}.pdf", [
                                'mime' => 'application/pdf',
                            ]);
                    }
                );
                Log::info("Booking receipt email with PDF attachment sent to {$booking->user->email} for Booking #{$booking->id}");
            } else {
                Log::warning("Could not send booking receipt: user or user email is missing.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send booking receipt email for Booking #{$payment->booking_id}: " . $e->getMessage());
        }

        return $payment->fresh(['booking.room:id,name', 'booking.hotel:id,name']);
    }

    /**
     * Refund payment
     */
    public function refund(Payment $payment, float $percentage = 0.5): array
    {
        try {
            $refundAmount = $payment->amount * $percentage;

            $refund = Refund::create([
                'payment_intent' => $payment->stripe_payment_intent_id,
                'amount'         => (int) round($refundAmount * 100),
            ]);

            $payment->update([
                'status'           => 'refunded',
                'refunded_amount'  => $refundAmount,
                'stripe_refund_id' => $refund->id,
            ]);

            return [
                'refunded'         => true,
                'amount'           => $refundAmount,
                'stripe_refund_id' => $refund->id,
            ];

        } catch (\Exception $e) {
            Log::error("Stripe Refund Error: " . $e->getMessage());
            throw $e;
        }
    }
}