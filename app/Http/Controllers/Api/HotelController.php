<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class HotelController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Hotel::query();

        // If user is hotel owner, show only their hotels
        if ($request->user() && $request->user()->isHotelOwner()) {
            $query->where('user_id', $request->user()->id);
        }

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

        // Guests filter: hotels that have at least one room
        // which can accommodate the requested number of guests.
        if ($request->filled('guests')) {
            $guests = (int) $request->get('guests');

            if ($guests > 0) {
                $query->whereHas('rooms', function (Builder $q) use ($guests) {
                    $q->where('max_guests', '>=', $guests);
                });
            }
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

        // Include max capacity per hotel based on related rooms
        $query->withMax('rooms as max_guests_capacity', 'max_guests');

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
        // Check authorization
        if (Gate::denies('create', Hotel::class)) {
            return $this->error(['You do not have permission to create hotels.'], 403);
        }

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

        $data = $request->except(['rating', 'reviews_count']);

        // If user is hotel owner, set user_id automatically
        if ($request->user() && $request->user()->isHotelOwner()) {
            $data['user_id'] = $request->user()->id;
        }

        $hotel = Hotel::create($data + [
            'rating' => 0,
            'reviews_count' => 0,
        ]);

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
            ['Hotel retrieved successfully server.']
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

        // Check authorization
        if (Gate::denies('update', $hotel)) {
            return $this->error(['You do not have permission to update this hotel.'], 403);
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

        // لا نسمح بتعديل rating و reviews_count من لوحة التحكم
        $data = $request->except(['rating', 'reviews_count']);

        $hotel->update($data);

        return $this->success(
            ['hotel' => $hotel->fresh()],
            ['Hotel updated successfully.']
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::find($id);

        if (!$hotel) {
            return $this->error(['Hotel not found.'], 404);
        }

        // Check authorization
        if (Gate::denies('delete', $hotel)) {
            return $this->error(['You do not have permission to delete this hotel.'], 403);
        }

        $hotel->delete();

        return $this->success(
            null,
            ['Hotel deleted successfully.']
        );
    }

    /**
     * Upload images for a hotel
     */
    public function uploadImages(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::find($id);

        if (!$hotel) {
            return $this->error(['Hotel not found.'], 404);
        }

        // Check authorization
        if (Gate::denies('update', $hotel)) {
            return $this->error(['You do not have permission to update this hotel.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB max per image
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $uploadedImages = [];
        $currentImages = $hotel->images ?? [];

        foreach ($request->file('images') as $image) {
            $path = $image->store('hotels/' . $hotel->id, 'public');
            $url = 'http://127.0.0.1:8000/storage/' . $path;
            $uploadedImages[] = $url;
        }

        // Merge with existing images
        $allImages = array_merge($currentImages, $uploadedImages);

        $hotel->update(['images' => $allImages]);

        return $this->success(
            [
                'hotel' => $hotel->fresh(),
                'uploaded_images' => $uploadedImages,
            ],
            ['Images uploaded successfully.']
        );
    }

    /**
     * Delete an image from a hotel
     */
    public function deleteImage(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::find($id);

        if (!$hotel) {
            return $this->error(['Hotel not found.'], 404);
        }

        // Check authorization
        if (Gate::denies('update', $hotel)) {
            return $this->error(['You do not have permission to update this hotel.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'image_url' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $imageUrl = $request->image_url;
        $currentImages = $hotel->images ?? [];

        // Remove image from array
        $updatedImages = array_filter($currentImages, function ($img) use ($imageUrl) {
            return $img !== $imageUrl;
        });

        // Delete file from storage if it exists
        if (str_starts_with($imageUrl, '/storage/')) {
            $filePath = str_replace('/storage/', '', $imageUrl);
            Storage::disk('public')->delete($filePath);
        }

        $hotel->update(['images' => array_values($updatedImages)]);

        return $this->success(
            [
                'hotel' => $hotel->fresh(),
            ],
            ['Image deleted successfully.']
        );
    }
}
