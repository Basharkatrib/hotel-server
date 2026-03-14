<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'label'];

    /**
     * Get the staff members that have this permission.
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(HotelStaff::class, 'hotel_staff_permissions');
    }
}
