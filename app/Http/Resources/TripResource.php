<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'pickup_address'  => $this->pickup_address,
            'pickup_lat'      => $this->pickup_lat,
            'pickup_lng'      => $this->pickup_lng,
            'dropoff_address' => $this->dropoff_address,
            'dropoff_lat'     => $this->dropoff_lat,
            'dropoff_lng'     => $this->dropoff_lng,
            'vehicle_type'    => $this->vehicle_type,
            'distance_km'     => $this->distance_km,
            'amount'          => $this->amount,
            'commission'      => $this->commission,
            'driver_net'      => $this->driver_net,
            'status'          => $this->status,
            'started_at'      => $this->started_at?->toDateTimeString(),
            'completed_at'    => $this->completed_at?->toDateTimeString(),
            'created_at'      => $this->created_at?->toDateTimeString(),
            'user'            => $this->whenLoaded('user', fn() => [
                'id'       => $this->user->id,
                'name'     => $this->user->first_name . ' ' . $this->user->last_name,
                'phone'    => $this->user->phone,
            ]),
            'driver'          => $this->whenLoaded('driver', fn() => [
                'id'            => $this->driver->id,
                'name'          => $this->driver->first_name . ' ' . $this->driver->last_name,
                'phone'         => $this->driver->phone,
                'vehicle_plate' => $this->driver->vehicle_plate,
                'vehicle_color' => $this->driver->vehicle_color,
            ]),
            'payment'         => $this->whenLoaded('payment', fn() => [
                'method' => $this->payment?->method,
                'status' => $this->payment?->status,
                'amount' => $this->payment?->amount,
            ]),
        ];
    }
}
