<?php

namespace App\Http\Resources\Book;

use Illuminate\Http\Request;

class GoogleBookResource extends GoogleBookBaseResource
{
    public function toArray(Request $request): array
    {
        $info = $this->resource['volumeInfo'] ?? [];

        return [
            'google_volume_id' => $this->resource['id'] ?? null,
            'title' => $info['title'] ?? null,
            'authors' => $info['authors'] ?? [],
            'language' => $info['language'] ?? null,
            'thumbnail' => $this->secureUrl($info['imageLinks']['thumbnail'] ?? null),
        ];
    }
}
