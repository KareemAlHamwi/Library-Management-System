<?php

namespace App\Http\Resources\Book;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class GoogleBookBaseResource extends JsonResource
{
    protected function secureUrl(?string $url): ?string
    {
        return $url ? str_replace('http://', 'https://', $url) : null;
    }
}
