<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Jobs\SendBookingConfirmationEmail;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for a booking.
     */
    public function createIntent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => ['required', 'exists:bookings,id'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $booking = Booking::with(['room', 'hotel'])
            ->where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return $this->error(['Booking not found or unauthorized.'], 404);
        }

        if ($booking->status !== 'pending') {
            return $this->error(['This booking has already been processed.'], 400);
        }

        try {
            // Create or retrieve payment record
            $payment = Payment::firstOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => $booking->total_amount,
                    'currency' => 'usd',
                    'status' => 'pending',
                    'payment_method' => 'card',
                ]
            );

            // Create Stripe PaymentIntent
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($booking->total_amount * 100), // Convert to cents
                'currency' => 'usd',
                'metadata' => [
                    'booking_id' => $booking->id,
                    'user_id' => $request->user()->id,
                    'hotel_name' => $booking->hotel->name,
                    'room_name' => $booking->room->name,
                ],
                'description' => "Booking for {$booking->hotel->name} - {$booking->room->name}",
            ]);

            // Update payment with Stripe payment intent ID
            $payment->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            return $this->success([
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $booking->total_amount,
            ], ['Payment intent created successfully.']);

        } catch (\Exception $e) {
            Log::error('Stripe PaymentIntent creation failed: ' . $e->getMessage());
            return $this->error(['Failed to create payment intent. Please try again.'], 500);
        }
    }

    /**
     * Confirm payment after successful Stripe payment.
     */
    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        try {
            // Retrieve payment intent from Stripe
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            if ($paymentIntent->status !== 'succeeded') {
                return $this->error(['Payment has not been completed yet.'], 400);
            }

            // Find payment record
            $payment = Payment::where('stripe_payment_intent_id', $request->payment_intent_id)->first();

            if (!$payment) {
                return $this->error(['Payment record not found.'], 404);
            }

            // Check if already processed
            if ($payment->status === 'succeeded') {
                return $this->success([
                    'booking' => $payment->booking->load(['room', 'hotel']),
                    'payment' => $payment,
                ], ['Payment already confirmed.']);
            }

            // Extract card details
            $paymentMethod = $paymentIntent->charges->data[0]->payment_method_details ?? null;
            $cardDetails = $paymentMethod->card ?? null;

            // Update payment record
            $payment->update([
                'status' => 'succeeded',
                'stripe_charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                'card_last4' => $cardDetails->last4 ?? null,
                'card_brand' => $cardDetails->brand ?? null,
                'paid_at' => now(),
            ]);

            // Update booking status
            $payment->booking->update(['status' => 'confirmed']);

            // ⭐ إرسال Email Confirmation عبر Queue
            SendBookingConfirmationEmail::dispatch($payment->booking, $payment);

            return $this->success([
                'booking' => $payment->booking->load(['room', 'hotel']),
                'payment' => $payment,
            ], ['Payment confirmed successfully.']);

        } catch (\Exception $e) {
            Log::error('Payment confirmation failed: ' . $e->getMessage());
            return $this->error(['Failed to confirm payment. Please contact support.'], 500);
        }
    }

    /**
     * Handle Stripe webhook events.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid webhook payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid webhook signature: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentSuccess($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentFailure($paymentIntent);
                break;

            default:
                Log::info('Unhandled webhook event type: ' . $event->type);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle successful payment webhook.
     */
    private function handlePaymentSuccess($paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment && $payment->status !== 'succeeded') {
            $payment->markAsPaid();
            
            // ⭐ إرسال Email Confirmation عبر Queue (للـ webhook)
            SendBookingConfirmationEmail::dispatch($payment->booking, $payment);
            
            Log::info("Payment {$payment->id} marked as paid via webhook.");
        }
    }

    /**
     * Handle failed payment webhook.
     */
    private function handlePaymentFailure($paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment && $payment->status !== 'failed') {
            $payment->markAsFailed();
            Log::info("Payment {$payment->id} marked as failed via webhook.");
        }
    }
}
