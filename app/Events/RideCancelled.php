<?php

namespace App\Events;

use App\Models\Ride;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Ride $ride,
        public User $cancelledBy
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('user.' . $this->ride->passenger_id),
        ];

        if ($this->ride->driver_id) {
            $channels[] = new PrivateChannel('user.' . $this->ride->driver_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'ride.cancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->ride->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->cancelledBy->id,
            'cancelled_by_role' => $this->cancelledBy->role,
            'reason' => $this->ride->cancellation_reason,
            'cancelled_at' => $this->ride->cancelled_at?->toISOString(),
        ];
    }
}
