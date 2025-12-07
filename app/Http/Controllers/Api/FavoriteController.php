<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Hotel;
use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of user's favorites.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $favorites = Favorite::where('user_id', $user->id)
            ->with(['favoritable' => function ($query) {
                if ($query->getModel() instanceof Hotel) {
                    $query->select('id', 'name', 'slug', 'city', 'country', 'price_per_night', 'rating', 'images');
                } elseif ($query->getModel() instanceof Room) {
                    $query->with('hotel:id,name,slug,city,country')
                          ->select('id', 'hotel_id', 'name', 'type', 'price_per_night', 'images', 'max_guests');
                }
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Separate hotels and rooms with favorite_id
        $hotels = [];
        $rooms = [];

        foreach ($favorites->items() as $favorite) {
            $item = $favorite->favoritable->toArray();
            $item['favorite_id'] = $favorite->id;
            
            if ($favorite->favoritable instanceof Hotel) {
                $hotels[] = $item;
            } elseif ($favorite->favoritable instanceof Room) {
                $rooms[] = $item;
            }
        }

        return $this->success([
            'hotels' => $hotels,
            'rooms' => $rooms,
            'pagination' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ],
        ], ['Favorites retrieved successfully.']);
    }

    /**
     * Store a newly created favorite.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'favoritable_type' => ['required', 'in:hotel,room'],
            'favoritable_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $user = $request->user();
        $favoritableType = $request->favoritable_type === 'hotel' ? Hotel::class : Room::class;
        $favoritableId = $request->favoritable_id;

        // Check if the favoritable item exists
        $favoritable = $favoritableType::find($favoritableId);
        if (!$favoritable) {
            return $this->error(['Item not found.'], 404);
        }

        // Check if already favorited
        $existingFavorite = Favorite::where('user_id', $user->id)
            ->where('favoritable_type', $favoritableType)
            ->where('favoritable_id', $favoritableId)
            ->first();

        if ($existingFavorite) {
            return $this->error(['Item is already in favorites.'], 400);
        }

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'favoritable_type' => $favoritableType,
            'favoritable_id' => $favoritableId,
        ]);

        return $this->success(
            ['favorite' => $favorite->load('favoritable')],
            ['Item added to favorites successfully.'],
            201
        );
    }

    /**
     * Remove the specified favorite.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        
        $favorite = Favorite::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$favorite) {
            return $this->error(['Favorite not found.'], 404);
        }

        $favorite->delete();

        return $this->success(
            null,
            ['Item removed from favorites successfully.']
        );
    }

    /**
     * Remove favorite by favoritable type and id.
     */
    public function remove(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'favoritable_type' => ['required', 'in:hotel,room'],
            'favoritable_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $user = $request->user();
        $favoritableType = $request->favoritable_type === 'hotel' ? Hotel::class : Room::class;
        $favoritableId = $request->favoritable_id;

        $favorite = Favorite::where('user_id', $user->id)
            ->where('favoritable_type', $favoritableType)
            ->where('favoritable_id', $favoritableId)
            ->first();

        if (!$favorite) {
            return $this->error(['Favorite not found.'], 404);
        }

        $favorite->delete();

        return $this->success(
            null,
            ['Item removed from favorites successfully.']
        );
    }

    /**
     * Check if an item is favorited by the user.
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'favoritable_type' => ['required', 'in:hotel,room'],
            'favoritable_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $user = $request->user();
        $favoritableType = $request->favoritable_type === 'hotel' ? Hotel::class : Room::class;
        $favoritableId = $request->favoritable_id;

        $isFavorited = Favorite::where('user_id', $user->id)
            ->where('favoritable_type', $favoritableType)
            ->where('favoritable_id', $favoritableId)
            ->exists();

        $favoriteId = null;
        if ($isFavorited) {
            $favorite = Favorite::where('user_id', $user->id)
                ->where('favoritable_type', $favoritableType)
                ->where('favoritable_id', $favoritableId)
                ->first();
            $favoriteId = $favorite->id;
        }

        return $this->success([
            'is_favorited' => $isFavorited,
            'favorite_id' => $favoriteId,
        ], ['Favorite status retrieved successfully.']);
    }
}
