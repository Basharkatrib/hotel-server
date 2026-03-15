<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\EntryLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FaceVerificationController extends Controller
{
    use ApiResponse;

    /**
     * Register face descriptor for a booking.
     * This is typically called from the reception (Filament or Backend).
     */
    public function registerFace(Request $request, Booking $booking): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'descriptor' => 'required|array|size:128',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        // Only owner or authorized staff can register face
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->getHotelIds()) {
             return $this->error(['Unauthorized'], 403);
        }

        if (!$user->isAdmin() && !in_array($booking->hotel_id, $user->getHotelIds())) {
            return $this->error(['Unauthorized for this hotel'], 403);
        }

        $booking->face_descriptor = json_encode($request->descriptor);
        $booking->save();

        return $this->success(null, ['Face registered successfully for booking #' . $booking->id]);
    }

    /**
     * Verify a captured face against all active bookings for a hotel.
     */
    public function verifyFace(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'hotel_id' => 'required|exists:hotels,id',
            'descriptor' => 'required|array|size:128',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $hotelId = $request->hotel_id;
        $capturedDescriptor = $request->descriptor;

        // Fetch all bookings for this hotel that are currently "confirmed" or "completed" (in-house)
        // Adjust status as needed. "confirmed" usually means they haven't checked out yet.
        $bookings = Booking::where('hotel_id', $hotelId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereNotNull('face_descriptor')
            ->get();

        $bestMatch = null;
        $minDistance = 1.0; // Standard threshold is 0.6
        $threshold = 0.6;

        foreach ($bookings as $booking) {
            $storedDescriptor = json_decode($booking->face_descriptor);
            
            if (!$storedDescriptor) continue;

            $distance = $this->euclideanDistance($capturedDescriptor, $storedDescriptor);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestMatch = $booking;
            }
        }

        if ($bestMatch && $minDistance < $threshold) {
             // Record the entry
             EntryLog::create([
                 'hotel_id' => $hotelId,
                 'booking_id' => $bestMatch->id,
                 'room_id' => $bestMatch->room_id,
                 'verified_at' => now(),
             ]);

             // We found a match!
             return $this->success([
                 'match' => true,
                 'booking_id' => $bestMatch->id,
                 'guest_name' => $bestMatch->guest_name,
                 'room_number' => $bestMatch->room->name ?? 'N/A',
                 'distance' => $minDistance,
                 'confidence' => round((1 - $minDistance) * 100, 2) . '%'
             ], ['Identity verified! Welcome ' . $bestMatch->guest_name]);
        }

        return $this->error(['No match found. Access denied.'], 404);
    }

    /**
     * Get bookings for a hotel to register their face.
     */
    public function getHotelBookings(Request $request, $hotelId): JsonResponse
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !in_array($hotelId, $user->getHotelIds())) {
            return $this->error(['Unauthorized'], 403);
        }

        $bookings = Booking::where('hotel_id', $hotelId)
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereNull('face_descriptor')
            ->orderBy('check_in_date', 'asc')
            ->select('id', 'guest_name', 'room_id', 'check_in_date')
            ->with('room:id,name')
            ->get();

        return $this->success(['bookings' => $bookings], ['Bookings fetched successfully.']);
    }

    /**
     * Calculate Euclidean distance between two descriptors.
     */
    private function euclideanDistance(array $desc1, array $desc2): float
    {
        $sum = 0;
        for ($i = 0; $i < 128; $i++) {
            $diff = $desc1[$i] - $desc2[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    /**
     * Get owner's hotels for the login phase of the standalone app.
     */
    public function getOwnerHotels(): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->isHotelOwner() && !$user->isAdmin()) {
            return $this->error(['Only hotel owners can use this application.'], 403);
        }

        $hotels = Hotel::where('user_id', $user->id)
            ->select('id', 'name', 'address')
            ->get();

        return $this->success(['hotels' => $hotels], ['Hotels fetched successfully.']);
    }
}
