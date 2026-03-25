<?php

namespace App\Events;

use App\Models\Ride;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Ride $ride
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->ride->passenger_id),
            new PrivateChannel('user.' . $this->ride->driver_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ride.started';
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->ride->id,
            'status' => 'in_progress',
            'started_at' => $this->ride->started_at?->toISOString(),
        ];
    }
}
