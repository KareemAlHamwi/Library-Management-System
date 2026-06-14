<?php

namespace App\Http\Resources\Book;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'google_volume_id' => $this->google_volume_id,
            'title' => $this->title,
            'description' => $this->description,
            'cover_image' => $this->cover_image
                                            ? asset('storage/'.$this->cover_image)
                                            : null,
            'publisher' => $this->publisher,
            'published_date' => $this->published_date,
            'page_count' => $this->page_count,
            'isbn' => $this->isbn,
            'language' => $this->language,
            'price' => $this->price,
            'overall_rating' => $this->overall_rating,
            'reviewers_count' => $this->reviewers_count,
            'total_copies' => $this->total_copies,
            'available_copies' => $this->available_copies,
            'total_stock_copies' => $this->total_stock_copies,
            'available_stock_copies' => $this->available_stock_copies,
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])
            ),
            'authors' => $this->whenLoaded('authors', fn () => $this->authors->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'bio' => $a->bio,
            ])
            ),
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}
