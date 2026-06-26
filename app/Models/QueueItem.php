<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueItem extends Model
{
    protected $fillable = [
        'booking_id',
        'customer_name',
        'customer_phone',
        'ticket_number',
        'service_name',
        'status',
        'estimated_wait_minutes',
        'counter_name',
        'called_at',
        'completed_at'
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
