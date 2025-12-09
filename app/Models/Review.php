<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'title',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who wrote the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent reviewable model (hotel or room).
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include reviews for hotels.
     */
    public function scopeForHotels($query)
    {
        return $query->where('reviewable_type', 'hotel');
    }

    /**
     * Scope a query to only include reviews for rooms.
     */
    public function scopeForRooms($query)
    {
        return $query->where('reviewable_type', 'room');
    }

    /**
     * Scope a query to filter by rating.
     */
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }
}
