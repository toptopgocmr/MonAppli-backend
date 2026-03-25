<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $driverId,
        public float $lat,
        public float $lng,
        public string $status
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.map'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'driver.location';
    }

    public function broadcastWith(): array
    {
        return [
            'driver_id' => $this->driverId,
            'lat'       => $this->lat,
            'lng'       => $this->lng,
            'status'    => $this->status,
            'at'        => now()->toDateTimeString(),
        ];
    }
}
