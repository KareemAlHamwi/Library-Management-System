<?php

namespace App\Http\Resources\Borrow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'due_date' => $this->due_date->toDateString(),
            'returned_at' => $this->returned_at?->toDateTimeString(),
            'is_overdue' => $this->isOverdue(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'full_name' => $this->user->first_name.' '.$this->user->last_name,
            ]),
            'book' => $this->whenLoaded('book', fn () => [
                'id' => $this->book->id,
                'title' => $this->book->title,
                'cover_image' => $this->book->cover_image
                    ? asset('storage/'.$this->book->cover_image)
                    : null,
            ]),
            'fine' => $this->whenLoaded('fine', fn () => $this->fine ? new FineResource($this->fine) : null
            ),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
