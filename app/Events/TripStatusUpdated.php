<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Trip $trip) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('trip.' . $this->trip->id),
            new PrivateChannel('user.' . $this->trip->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trip.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'     => $this->trip->id,
            'status' => $this->trip->status,
        ];
    }
}
