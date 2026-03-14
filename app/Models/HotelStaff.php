<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HotelStaff extends Model
{
    use HasFactory;

    protected $table = 'hotel_staff';

    protected $fillable = [
        'user_id',
        'hotel_id',
        'position',
        'is_active',
    ];

    /**
     * Get the user associated with this staff member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the hotel associated with this staff member.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the permissions assigned to this staff member.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'hotel_staff_permissions');
    }
}
