<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'amount'          => $this->amount,
            'commission'      => $this->commission,
            'driver_net'      => $this->driver_net,
            'method'          => $this->method,
            'status'          => $this->status,
            'transaction_ref' => $this->transaction_ref,
            'country'         => $this->country,
            'city'            => $this->city,
            'paid_at'         => $this->paid_at?->toDateTimeString(),
            'created_at'      => $this->created_at?->toDateTimeString(),
            'user'            => $this->whenLoaded('user', fn() => [
                'id'    => $this->user->id,
                'name'  => $this->user->first_name . ' ' . $this->user->last_name,
                'phone' => $this->user->phone,
            ]),
            'driver'          => $this->whenLoaded('driver', fn() => [
                'id'    => $this->driver->id,
                'name'  => $this->driver->first_name . ' ' . $this->driver->last_name,
                'phone' => $this->driver->phone,
            ]),
            'trip'            => $this->whenLoaded('trip', fn() => [
                'id'     => $this->trip->id,
                'pickup' => $this->trip->pickup_address,
                'dropoff'=> $this->trip->dropoff_address,
            ]),
        ];
    }
}
