<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'full_name'  => $this->first_name . ' ' . $this->last_name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'status'     => $this->status,
            'role'       => $this->whenLoaded('role', fn() => [
                'id'          => $this->role->id,
                'name'        => $this->role->name,
                'permissions' => $this->role->permissions?->pluck('name'),
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
