<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('tenant.' . tenant('id') . '.queue'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'QueueUpdated';
    }
}
