<?php

namespace App\Http\Resources\Driver;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'first_name'     => $this->first_name,
            'last_name'      => $this->last_name,
            'full_name'      => $this->first_name . ' ' . $this->last_name,
            'phone'          => $this->phone,
            'profile_photo'  => $this->profile_photo,
            'birth_date'     => $this->birth_date,
            'birth_place'    => $this->birth_place,
            'country_birth'  => $this->country_birth,
            'status'         => $this->status,
            'driver_status'  => $this->driver_status,
            'vehicle' => [
                'plate'   => $this->vehicle_plate,
                'brand'   => $this->vehicle_brand,
                'model'   => $this->vehicle_model,
                'type'    => $this->vehicle_type,
                'color'   => $this->vehicle_color,
                'country' => $this->vehicle_country,
                'city'    => $this->vehicle_city,
                'lat'     => $this->vehicle_lat,
                'lng'     => $this->vehicle_lng,
            ],
            'documents' => [
                'id_card_front'          => $this->id_card_front,
                'id_card_back'           => $this->id_card_back,
                'license_front'          => $this->license_front,
                'license_back'           => $this->license_back,
                'vehicle_registration'   => $this->vehicle_registration,
                'insurance'              => $this->insurance,
                'id_card_expiry_date'    => $this->id_card_expiry_date,
                'license_expiry_date'    => $this->license_expiry_date,
            ],
            'wallet'          => $this->whenLoaded('wallet', fn() => [
                'balance'  => $this->wallet->balance,
                'currency' => $this->wallet->currency,
            ]),
            'latest_location' => $this->whenLoaded('latestLocation', fn() => [
                'lat'         => $this->latestLocation?->lat,
                'lng'         => $this->latestLocation?->lng,
                'recorded_at' => $this->latestLocation?->recorded_at,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
