<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelApplication extends Model
{
    protected $fillable = [
        'user_id', 'hotel_name', 'property_address', 'property_type',
        'legal_name', 'job_title', 'contact_email', 'contact_phone',
        'status', 'rejection_reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(HotelDocument::class, 'application_id');
    }
}