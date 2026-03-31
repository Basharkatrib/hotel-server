<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelDocument extends Model
{
    protected $fillable = [
        'application_id',
        'type',
        'original_name',
        'disk_path',
        'mime_type',
        'size',
    ];

    public function application()
    {
        return $this->belongsTo(HotelApplication::class);
    }
}