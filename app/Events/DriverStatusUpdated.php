<?php

namespace App\Events;

use App\Models\Driver\Driver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class DriverStatusUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public Driver $driver) {}

    public function broadcastOn(): Channel
    {
        return new Channel('drivers.status');
    }

    public function broadcastAs(): string
    {
        return 'status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'driver_id'  => $this->driver->id,
            'status'     => $this->driver->status,
            'first_name' => $this->driver->first_name,
            'last_name'  => $this->driver->last_name,
        ];
    }
}