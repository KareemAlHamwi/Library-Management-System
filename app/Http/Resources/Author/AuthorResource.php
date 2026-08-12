<?php

namespace App\Http\Resources\Author;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'bio' => $this->bio,
            'books_count' => $this->whenCounted('books'),
            'books' => $this->whenLoaded('books', fn () => $this->books->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'cover_image' => $b->cover_image
                                    ? asset('storage/'.$b->cover_image)
                                    : null,
            ])
            ),
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}
