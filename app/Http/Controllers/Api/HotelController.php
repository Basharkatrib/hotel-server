<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HotelController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Hotel::query();

        // Filters
        if ($request->has('type') && $request->type !== 'any') {
            $query->where('type', $request->type);
        }

        if ($request->has('min_price')) {
            $query->where('price_per_night', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price_per_night', '<=', $request->max_price);
        }

        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        if ($request->has('has_free_cancellation') && $request->has_free_cancellation) {
            $query->where('has_free_cancellation', true);
        }

        if ($request->has('has_spa_access') && $request->has_spa_access) {
            $query->where('has_spa_access', true);
        }

        if ($request->has('has_breakfast_included') && $request->has_breakfast_included) {
            $query->where('has_breakfast_included', true);
        }

        if ($request->has('is_featured') && $request->is_featured) {
            $query->where('is_featured', true);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['price_per_night', 'rating', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $hotels = $query->paginate($perPage);

        return $this->success([
            'hotels' => $hotels->items(),
            'pagination' => [
                'current_page' => $hotels->currentPage(),
                'last_page' => $hotels->lastPage(),
                'per_page' => $hotels->perPage(),
                'total' => $hotels->total(),
                'from' => $hotels->firstItem(),
                'to' => $hotels->lastItem(),
            ]
        ], ['Hotels retrieved successfully.']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['required', 'string'],
            'city' => ['nullable', 'string'],
            'country' => ['string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['integer', 'min:0', 'max:100'],
            'type' => ['required', 'in:hotel,room,entire_home'],
            'rating' => ['numeric', 'between:0,5'],
            'reviews_count' => ['integer', 'min:0'],
            'room_type' => ['nullable', 'string'],
            'bed_type' => ['nullable', 'string'],
            'room_size' => ['nullable', 'integer', 'min:0'],
            'available_rooms' => ['integer', 'min:0'],
            'distance_from_center' => ['nullable', 'numeric', 'min:0'],
            'distance_from_beach' => ['nullable', 'numeric', 'min:0'],
            'has_metro_access' => ['boolean'],
            'has_free_cancellation' => ['boolean'],
            'has_spa_access' => ['boolean'],
            'has_breakfast_included' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_getaway_deal' => ['boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $hotel = Hotel::create($request->all());

        return $this->success(
            ['hotel' => $hotel],
            ['Hotel created successfully.'],
            201
        );
    }

    /**
     * Display the specified resource by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $hotel = Hotel::where('slug', $slug)->first();

        if (!$hotel) {
            return $this->error(['Hotel not found.'], 404);
        }

        return $this->success(
            ['hotel' => $hotel],
            ['Hotel retrieved successfully.']
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::find($id);

        if (!$hotel) {
            return $this->error(['Hotel not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['string'],
            'city' => ['nullable', 'string'],
            'country' => ['string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'price_per_night' => ['numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['integer', 'min:0', 'max:100'],
            'type' => ['in:hotel,room,entire_home'],
            'rating' => ['numeric', 'between:0,5'],
            'reviews_count' => ['integer', 'min:0'],
            'room_type' => ['nullable', 'string'],
            'bed_type' => ['nullable', 'string'],
            'room_size' => ['nullable', 'integer', 'min:0'],
            'available_rooms' => ['integer', 'min:0'],
            'distance_from_center' => ['nullable', 'numeric', 'min:0'],
            'distance_from_beach' => ['nullable', 'numeric', 'min:0'],
            'has_metro_access' => ['boolean'],
            'has_free_cancellation' => ['boolean'],
            'has_spa_access' => ['boolean'],
            'has_breakfast_included' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_getaway_deal' => ['boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $hotel->update($request->all());

        return $this->success(
            ['hotel' => $hotel->fresh()],
            ['Hotel updated successfully.']
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $hotel = Hotel::find($id);

        if (!$hotel) {
            return $this->error(['Hotel not found.'], 404);
        }

        $hotel->delete();

        return $this->success(
            null,
            ['Hotel deleted successfully.']
        );
    }
}
