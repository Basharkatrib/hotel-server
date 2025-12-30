<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RoomReviewController extends Controller
{
    use ApiResponse;

    /**
     * Get all reviews for a room.
     */
    public function index(Request $request, Room $room): JsonResponse
    {
        try {
            $query = Review::where('reviewable_type', 'room')
                ->where('reviewable_id', $room->id)
                ->with('user:id,name,email');

            // Filter by rating
            if ($request->has('rating') && $request->rating) {
                $query->where('rating', $request->rating);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $allowedSortFields = ['created_at', 'rating'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            $sortOrder = $request->get('sort_order', 'desc');
            if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }

            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 10);
            $perPage = min(max(1, $perPage), 50);

            $reviews = $query->paginate($perPage);

            return $this->success([
                'reviews' => $reviews->items(),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching room reviews', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Failed to fetch reviews', 500);
        }
    }

    /**
     * Create a new review for a room.
     */
    public function store(StoreReviewRequest $request, Room $room): JsonResponse
    {
        try {
            // Check if user already reviewed this room
            $existingReview = Review::where('user_id', Auth::id())
                ->where('reviewable_type', 'room')
                ->where('reviewable_id', $room->id)
                ->first();

            if ($existingReview) {
                return $this->error('You have already reviewed this room.', 422);
            }

            DB::beginTransaction();

            $review = Review::create([
                'user_id' => Auth::id(),
                'reviewable_type' => 'room',
                'reviewable_id' => $room->id,
                'rating' => $request->rating,
                'title' => $request->title,
                'comment' => $request->comment,
            ]);

            $review->load('user:id,name,email');

            // Update room's average rating
            $this->updateRoomRating($room);

            DB::commit();

            return $this->success($review, 'Review created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating room review', [
                'room_id' => $room->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return $this->error('Failed to create review', 500);
        }
    }

    /**
     * Get review statistics for a room.
     */
    public function stats(Room $room): JsonResponse
    {
        try {
            $reviews = Review::where('reviewable_type', 'room')
                ->where('reviewable_id', $room->id)
                ->get();

            $stats = [
                'average_rating' => round($reviews->avg('rating') ?? 0, 2),
                'total_reviews' => $reviews->count(),
                'rating_distribution' => [
                    5 => $reviews->where('rating', 5)->count(),
                    4 => $reviews->where('rating', 4)->count(),
                    3 => $reviews->where('rating', 3)->count(),
                    2 => $reviews->where('rating', 2)->count(),
                    1 => $reviews->where('rating', 1)->count(),
                ],
            ];

            return $this->success($stats);
        } catch (\Exception $e) {
            Log::error('Error fetching room review stats', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Failed to fetch review statistics', 500);
        }
    }

    /**
     * Check if the authenticated user has reviewed the room.
     */
    public function check(Room $room): JsonResponse
    {
        try {
            $review = Review::where('user_id', Auth::id())
                ->where('reviewable_type', 'room')
                ->where('reviewable_id', $room->id)
                ->first();

            return $this->success([
                'has_reviewed' => $review !== null,
                'review_id' => $review?->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking room review', [
                'room_id' => $room->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return $this->error('Failed to check review status', 500);
        }
    }

    /**
     * Update room's average rating in the rooms table.
     */
    private function updateRoomRating(Room $room): void
    {
        $reviews = Review::where('reviewable_type', 'room')
            ->where('reviewable_id', $room->id)
            ->get();

        $averageRating = $reviews->avg('rating');
        $reviewsCount = $reviews->count();

        // Update room if it has rating and reviews_count columns
        if (Schema::hasColumn('rooms', 'rating')) {
            $room->update([
                'rating' => round($averageRating, 2),
                'reviews_count' => $reviewsCount,
            ]);
        }
    }
}








