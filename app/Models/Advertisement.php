<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Advertisement extends Model
{
    protected $fillable = [
        'hotel_id', 'title', 'description',
        'discount_type', 'discount_value',
        'applies_to', 'starts_at', 'ends_at', 'is_active'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'boolean',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && now()->between($this->starts_at, $this->ends_at);
    }

    public function applyDiscount(float $originalPrice): float
    {
        if (!$this->isCurrentlyActive()) return $originalPrice;

        return match($this->discount_type) {
            'percentage' => round($originalPrice * (1 - $this->discount_value / 100), 2),
            'fixed'      => max(0, round($originalPrice - $this->discount_value, 2)),
        };
    }
}