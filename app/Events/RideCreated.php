<?php

namespace App\Events;

use App\Models\Ride;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Ride $ride
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('rides'),
            new PresenceChannel('drivers.available'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ride.created';
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->ride->id,
            'pickup' => [
                'address' => $this->ride->pickup_address,
                'latitude' => $this->ride->pickup_latitude,
                'longitude' => $this->ride->pickup_longitude,
            ],
            'dropoff' => [
                'address' => $this->ride->dropoff_address,
                'latitude' => $this->ride->dropoff_latitude,
                'longitude' => $this->ride->dropoff_longitude,
            ],
            'price' => $this->ride->price,
            'vehicle_type' => $this->ride->vehicle_type,
            'created_at' => $this->ride->created_at->toISOString(),
        ];
    }
}
