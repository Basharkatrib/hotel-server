<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Hotel extends Model
{
    /** @use HasFactory<\Database\Factories\HotelFactory> */
    use HasFactory;

    /**
     * Get the rooms for the hotel.
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'price_per_night',
        'original_price',
        'discount_percentage',
        'type',
        'rating',
        'reviews_count',
        'room_type',
        'bed_type',
        'room_size',
        'available_rooms',
        'distance_from_center',
        'distance_from_beach',
        'has_metro_access',
        'has_free_cancellation',
        'has_spa_access',
        'has_breakfast_included',
        'is_featured',
        'is_getaway_deal',
        'images',
        'amenities',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'price_per_night' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_percentage' => 'integer',
        'rating' => 'decimal:1',
        'reviews_count' => 'integer',
        'room_size' => 'integer',
        'available_rooms' => 'integer',
        'distance_from_center' => 'decimal:2',
        'distance_from_beach' => 'decimal:2',
        'has_metro_access' => 'boolean',
        'has_free_cancellation' => 'boolean',
        'has_spa_access' => 'boolean',
        'has_breakfast_included' => 'boolean',
        'is_featured' => 'boolean',
        'is_getaway_deal' => 'boolean',
        'images' => 'array',
        'amenities' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Hotel $hotel) {
            if (empty($hotel->slug)) {
                $hotel->slug = static::generateUniqueSlug($hotel->name);
            }
        });

        static::updating(function (Hotel $hotel) {
            if ($hotel->isDirty('name')) {
                $hotel->slug = static::generateUniqueSlug($hotel->name, $hotel->id);
            }
        });
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
