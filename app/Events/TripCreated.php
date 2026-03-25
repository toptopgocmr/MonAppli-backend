<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Trip $trip) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('drivers'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trip.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->trip->id,
            'pickup_address' => $this->trip->pickup_address,
            'dropoff_address'=> $this->trip->dropoff_address,
            'vehicle_type'   => $this->trip->vehicle_type,
            'amount'         => $this->trip->amount,
        ];
    }
}
