<?php
namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;

class PaymentService
{


    public function __construct()
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createIntent(Booking $booking): array
    {
        $payment = Payment::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'amount'         => $booking->total_amount,
                'currency'       => 'usd',
                'status'         => 'pending',
                'payment_method' => 'card',
            ]
        );

        $paymentIntent = PaymentIntent::create([
            'amount'      => (int) ($booking->total_amount * 100),
            'currency'    => 'usd',
            'description' => "Booking for {$booking->hotel->name} - {$booking->room->name}",
            'metadata'    => [
                'booking_id' => $booking->id,
                'hotel_name' => $booking->hotel->name,
                'room_name'  => $booking->room->name,
            ],
        ]);

        $payment->update([
            'stripe_payment_intent_id' => $paymentIntent->id,
        ]);

        return [
            'client_secret'     => $paymentIntent->client_secret,
            'payment_intent_id' => $paymentIntent->id,
            'amount'            => $booking->total_amount,
        ];
    }


    public function confirmPayment(string $paymentIntentId): Payment
    {
        $stripeIntent = PaymentIntent::retrieve($paymentIntentId);

        if ($stripeIntent->status !== 'succeeded') {
            throw new \Exception('Payment has not been completed yet.');
        }

        $payment = Payment::where(
            'stripe_payment_intent_id', $paymentIntentId
        )->firstOrFail();

        if ($payment->status === 'succeeded') {
            return $payment;
        }

        $cardDetails = $stripeIntent->charges->data[0]
            ->payment_method_details->card ?? null;

        $payment->update([
            'status'           => 'succeeded',
            'stripe_charge_id' => $stripeIntent->charges->data[0]->id ?? null,
            'card_last4'       => $cardDetails->last4 ?? null,
            'card_brand'       => $cardDetails->brand ?? null,
            'paid_at'          => now(),
        ]);

        $payment->booking->update(['status' => 'confirmed']);

        Log::info("Payment {$payment->id} confirmed.");

        return $payment->fresh(['booking.room', 'booking.hotel']);
    }

    public function refund(Payment $payment, float $percentage = 0.5): array
    {
        try {
            $refundAmount = $payment->amount * $percentage;

            $refund = \Stripe\Refund::create([
                'payment_intent' => $payment->stripe_payment_intent_id,
                'amount'         => (int) ($refundAmount * 100),
            ]);

            $payment->update([
                'status'           => 'refunded',
                'refunded_amount'  => $refundAmount,
                'stripe_refund_id' => $refund->id,
            ]);

            return [
                'refunded' => true,
                'amount'   => $refundAmount,
                'stripe_refund_id' => $refund->id,
            ];
        } catch (\Exception $e) {
            Log::error("Stripe Refund Error: " . $e->getMessage());
            throw $e;
        }
    }
}