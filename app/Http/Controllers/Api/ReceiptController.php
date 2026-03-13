<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReceiptController extends Controller
{
    /**
     * Generate and download PDF receipt for a booking.
     */
    public function download(int $id, Request $request)
    {
        // Support token in query string for download windows
        if (!$request->user() && $request->has('token')) {
            \Illuminate\Support\Facades\Log::info('ReceiptController: Token received in request: ' . $request->token);
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($token && $token->tokenable) {
                \Illuminate\Support\Facades\Log::info('ReceiptController: Valid token found for user ID: ' . $token->tokenable->id);
                auth()->login($token->tokenable);
            } else {
                \Illuminate\Support\Facades\Log::warning('ReceiptController: Invalid token provided.');
            }
        }

        $booking = Booking::with(['room', 'hotel', 'payment', 'user'])->find($id);

        if (!$booking) {
            abort(404, 'Booking not found.');
        }

        // Check authorization
        if (Gate::denies('view', $booking)) {
            abort(403, 'You do not have permission to view this receipt.');
        }

        try {
            $pdf = Pdf::loadView('receipts.booking', compact('booking'));
            return $pdf->download("receipt-booking-{$booking->id}.pdf");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ReceiptController: PDF generation failed: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            return response()->json([
                'status' => false,
                'data' => null,
                'messages' => ['PDF generation failed: ' . $e->getMessage()],
                'code' => 500
            ], 500);
        }
    }
    
    /**
     * Preview PDF receipt in browser.
     */
    public function preview(int $id)
    {
        $booking = Booking::with(['room', 'hotel', 'payment', 'user'])->find($id);

        if (!$booking) {
            abort(404, 'Booking not found.');
        }

        // Check authorization
        if (Gate::denies('view', $booking)) {
            abort(403, 'You do not have permission to view this receipt.');
        }

        $pdf = Pdf::loadView('receipts.booking', compact('booking'));
        
        return $pdf->stream("receipt-booking-{$booking->id}.pdf");
    }
}
