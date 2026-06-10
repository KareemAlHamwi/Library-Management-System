<?php

namespace App\Http\Resources\Book;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GoogleBookDetailedResource extends GoogleBookBaseResource
{
    public function toArray(Request $request): array
    {
        $info = $this->resource['volumeInfo'] ?? [];
        $identifierMap = Collection::make($info['industryIdentifiers'] ?? [])
            ->keyBy('type')
            ->map(fn ($v) => $v['identifier']);

        return [
            'google_volume_id' => $this->resource['id'] ?? null,
            'title' => $info['title'] ?? null,
            'description' => $info['description'] ?? null,
            'publisher' => $info['publisher'] ?? null,
            'published_date' => $info['publishedDate'] ?? null,
            'page_count' => isset($info['pageCount']) ? (int) $info['pageCount'] : null,
            'isbn' => $identifierMap->get('ISBN_13') ?? $identifierMap->get('ISBN_10'),
            'language' => $info['language'] ?? null,
            'authors' => $info['authors'] ?? [],
            'categories' => $info['categories'] ?? [],
            'thumbnail' => $this->secureUrl($info['imageLinks']['thumbnail'] ?? null),
        ];
    }
}
