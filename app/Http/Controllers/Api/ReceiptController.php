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
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($token && $token->tokenable) {
                auth()->login($token->tokenable);
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

        $pdf = Pdf::loadView('receipts.booking', compact('booking'));
        
        return $pdf->download("receipt-booking-{$booking->id}.pdf");
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
