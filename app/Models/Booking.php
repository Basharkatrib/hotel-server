<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'hotel_id',
        'check_in_date',
        'check_out_date',
        'total_nights',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guests_count',
        'rooms_count',
        'guests_details',
        'price_per_night',
        'subtotal',
        'service_fee',
        'taxes',
        'total_amount',
        'status',
        'special_requests',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'total_nights' => 'integer',
        'guests_count' => 'integer',
        'rooms_count' => 'integer',
        'guests_details' => 'array',
        'price_per_night' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'taxes' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the room that was booked.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the hotel that was booked.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the payment associated with the booking.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Scope a query to only include active bookings.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Scope a query to only include upcoming bookings.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('check_in_date', '>=', Carbon::today())
                     ->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Scope a query to only include past bookings.
     */
    public function scopePast($query)
    {
        return $query->where('check_out_date', '<', Carbon::today())
                     ->orWhere('status', 'completed');
    }

    /**
     * Calculate total amount based on nights and fees.
     */
    public function calculateTotal(): float
    {
        $this->subtotal = $this->price_per_night * $this->total_nights;
        $this->service_fee = $this->subtotal * 0.028; // 2.8% service fee
        $this->taxes = $this->subtotal * 0.0164; // 1.64% taxes
        $this->total_amount = $this->subtotal + $this->service_fee + $this->taxes;

        return $this->total_amount;
    }

    /**
     * Check if this booking overlaps with given dates.
     */
    public function isOverlapping($checkIn, $checkOut): bool
    {
        $checkIn = Carbon::parse($checkIn);
        $checkOut = Carbon::parse($checkOut);

        return $checkIn->lt($this->check_out_date) && $checkOut->gt($this->check_in_date);
    }

    /**
     * Check if the booking can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) 
               && $this->check_in_date->isFuture();
    }
}
