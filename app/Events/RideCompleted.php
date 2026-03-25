<?php

namespace App\Events;

use App\Models\Ride;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideCompleted implements ShouldBroadcast
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
        return 'ride.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->ride->id,
            'status' => 'completed',
            'completed_at' => $this->ride->completed_at?->toISOString(),
            'price' => $this->ride->price,
        ];
    }
}
