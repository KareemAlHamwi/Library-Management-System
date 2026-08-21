<?php

namespace App\Http\Resources\Event;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'prompt' => $this->prompt,
            'external_link' => $this->external_link,
            'status' => $this->status?->value, // Fixes line 18
            'starts_at' => $this->starts_at?->toDateTimeString(),
            'ends_at' => $this->ends_at?->toDateTimeString(),
            'supervisor' => $this->whenLoaded('supervisor', fn () => [
                'id' => $this->supervisor->id,
                'full_name' => $this->supervisor->first_name.' '.$this->supervisor->last_name,
            ]),
            'book' => $this->whenLoaded('book', fn () => [
                'id' => $this->book->id,
                'title' => $this->book->title,
                'cover' => $this->book->cover_image
                    ? asset('storage/'.$this->book->cover_image)
                    : null,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
