<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\PaymentService;
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

    public function __construct(
        private PaymentService $paymentService
    ) {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createIntent(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
        ]);

        $booking = Booking::with(['room', 'hotel'])
            ->where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($booking->status !== 'pending') {
            return $this->error(['This booking has already been processed.'], 400);
        }

        try {
            $data = $this->paymentService->createIntent($booking);
            return $this->success($data, ['Payment intent created.']);
        } catch (\Exception $e) {
            Log::error('createIntent failed: ' . $e->getMessage());
            return $this->error(['Failed to create payment intent.'], 500);
        }
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        try {
            $payment = $this->paymentService->confirmPayment(
                $request->payment_intent_id
            );

            return $this->success([
                'booking' => $payment->booking,
                'payment' => $payment,
            ], ['Payment confirmed successfully.']);

        } catch (\Exception $e) {
            Log::error('confirm failed: ' . $e->getMessage());
            return $this->error([$e->getMessage()], 500);
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid webhook'], 400);
        }

        match ($event->type) {
            'payment_intent.succeeded' =>
                // نفس الـ method المستخدمة في confirm() ✅
                $this->paymentService->confirmPayment(
                    $event->data->object->id
                ),

            'payment_intent.payment_failed' =>
                Payment::where('stripe_payment_intent_id', $event->data->object->id)
                       ->first()
                       ?->markAsFailed(),

            default => Log::info('Unhandled webhook: ' . $event->type),
        };

        return response()->json(['status' => 'success']);
    }
}