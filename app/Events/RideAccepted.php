<?php

namespace App\Events;

use App\Models\Ride;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Ride $ride
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->ride->passenger_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ride.accepted';
    }

    public function broadcastWith(): array
    {
        $driver = $this->ride->driver;
        $profile = $driver?->driverProfile;

        return [
            'ride_id' => $this->ride->id,
            'status' => 'accepted',
            'driver' => $driver ? [
                'id' => $driver->id,
                'name' => $driver->full_name,
                'phone' => $driver->phone,
                'avatar' => $driver->avatar,
                'rating' => $profile?->rating_average,
                'vehicle' => $profile ? [
                    'brand' => $profile->vehicle_brand,
                    'model' => $profile->vehicle_model,
                    'color' => $profile->vehicle_color,
                    'plate' => $profile->vehicle_plate_number,
                ] : null,
                'location' => $profile ? [
                    'latitude' => $profile->current_latitude,
                    'longitude' => $profile->current_longitude,
                ] : null,
            ] : null,
        ];
    }
}
