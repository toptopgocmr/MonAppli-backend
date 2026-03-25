<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'full_name'     => $this->first_name . ' ' . $this->last_name,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'country'       => $this->country,
            'city'          => $this->city,
            'profile_photo' => $this->profile_photo,
            'status'        => $this->status,
            'created_at'    => $this->created_at?->toDateTimeString(),
        ];
    }
}
