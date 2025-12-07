<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;

class Room extends Model
{
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'type',
        'size',
        'max_guests',
        'single_beds',
        'double_beds',
        'king_beds',
        'queen_beds',
        'price_per_night',
        'original_price',
        'discount_percentage',
        'is_available',
        'has_breakfast',
        'has_wifi',
        'has_ac',
        'has_tv',
        'has_minibar',
        'has_safe',
        'has_balcony',
        'has_bathtub',
        'has_shower',
        'no_smoking',
        'view',
        'images',
        'rating',
        'reviews_count',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'size' => 'integer',
        'max_guests' => 'integer',
        'single_beds' => 'integer',
        'double_beds' => 'integer',
        'king_beds' => 'integer',
        'queen_beds' => 'integer',
        'price_per_night' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_percentage' => 'integer',
        'is_available' => 'boolean',
        'has_breakfast' => 'boolean',
        'has_wifi' => 'boolean',
        'has_ac' => 'boolean',
        'has_tv' => 'boolean',
        'has_minibar' => 'boolean',
        'has_safe' => 'boolean',
        'has_balcony' => 'boolean',
        'has_bathtub' => 'boolean',
        'has_shower' => 'boolean',
        'no_smoking' => 'boolean',
        'rating' => 'decimal:1',
        'reviews_count' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'images' => 'array',
    ];

    /**
     * Get the hotel that owns the room.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get total number of beds in the room.
     */
    public function getTotalBedsAttribute(): int
    {
        return $this->single_beds + $this->double_beds + $this->king_beds + $this->queen_beds;
    }

    /**
     * Check if room has discount.
     */
    public function getHasDiscountAttribute(): bool
    {
        return $this->original_price && $this->original_price > $this->price_per_night;
    }

    /**
     * Get the bookings for the room.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Check if room is available for given dates.
     */
    public function isAvailableForDates($checkIn, $checkOut): bool
    {
        $checkIn = Carbon::parse($checkIn);
        $checkOut = Carbon::parse($checkOut);

        // Check if room is generally available
        if (!$this->is_available || !$this->is_active) {
            return false;
        }

        // Check for overlapping bookings
        $overlappingBookings = $this->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where(function ($q) use ($checkIn, $checkOut) {
                    $q->where('check_in_date', '<', $checkOut)
                      ->where('check_out_date', '>', $checkIn);
                });
            })
            ->exists();

        return !$overlappingBookings;
    }

    /**
     * Get the favorites for this room.
     */
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    /**
     * Check if a user has favorited this room.
     */
    public function isFavoritedBy(int $userId): bool
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }
}
