<?php

namespace App\Http\Resources\Book;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'cover_image' => $this->cover_image
                                     ? asset('storage/'.$this->cover_image)
                                     : null,
            'price' => $this->price,
            'overall_rating' => $this->overall_rating,
            'reviewers_count' => $this->reviewers_count,
            'available_copies' => $this->available_copies,
            'language' => $this->language,
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->pluck('name')
            ),
            'authors' => $this->whenLoaded('authors', fn () => $this->authors->pluck('name')
            ),
        ];
    }
}
