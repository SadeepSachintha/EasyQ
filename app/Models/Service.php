<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_minutes',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
