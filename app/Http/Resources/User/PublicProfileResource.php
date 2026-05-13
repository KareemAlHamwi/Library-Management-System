<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->first_name.' '.$this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'bio' => $this->bio,
            'role' => $this->role->value,
            'purchase_points' => $this->purchase_points,
            'event_points' => $this->event_points,
        ];
    }
}
