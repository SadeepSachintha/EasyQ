<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_email',
        'service_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function queueItem(): HasOne
    {
        return $this->hasOne(QueueItem::class);
    }
}
