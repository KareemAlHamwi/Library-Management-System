<?php

namespace App\Http\Resources\Category;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image ? asset('storage/'.$this->image) : null,
            'books_count' => $this->whenCounted('books'),
            'books' => $this->whenLoaded('books', fn () => $this->books->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'cover_image' => $b->cover_image ? asset('storage/'.$b->cover_image) : null,
            ])),
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}
