<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\JsonResponse;

class AdvertisementController extends Controller
{
    /**
     * Get all currently active advertisements for the news ticker.
     */
    public function index(): JsonResponse
    {
        $ads = Advertisement::with('hotel:id,name,slug')
            ->active()
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'advertisements' => $ads
            ]
        ]);
    }
}
