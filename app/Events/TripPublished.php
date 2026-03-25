<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripPublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Trip $trip) {}

    public function broadcastOn(): array
    {
        // Canal public — tous les clients reçoivent la notif
        return [
            new Channel('clients'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trip.published';
    }

    public function broadcastWith(): array
    {
        return [
            'id'               => $this->trip->id,
            'pickup_address'   => $this->trip->pickup_address,
            'departure_city'   => $this->trip->departure_city,
            'dropoff_address'  => $this->trip->dropoff_address,
            'destination_city' => $this->trip->destination_city,
            'amount'           => $this->trip->amount,
            'available_seats'  => $this->trip->available_seats,
            'luggage_kg'       => $this->trip->luggage_kg,
            'departure_time'   => $this->trip->departure_time,
            'vehicle_type'     => $this->trip->vehicle_type,
            'driver'           => [
                'name' => $this->trip->driver->first_name . ' ' . $this->trip->driver->last_name,
            ],
            'sound'            => 'new_trip', // Flutter jouera ce son
        ];
    }
}