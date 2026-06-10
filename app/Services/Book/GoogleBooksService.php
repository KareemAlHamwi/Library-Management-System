<?php

namespace App\Services\Book;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

enum PrintType: string
{
    case BOOKS = 'books';
    case MAGAZINES = 'magazines';
}

enum OrderBy: string
{
    case RELEVANCE = 'relevance';
    case NEWEST = 'newest';
}

class GoogleBooksService
{
    private const BASE_URL = 'https://www.googleapis.com/books/v1/volumes';

    private const CACHE_TTL_DAYS = 7;

    private const MAX_RESULTS_CAP = 40;

    private const REQUEST_TIMEOUT = 30;

    private string $apiKey;

    public function __construct()
    {
        $key = config('services.google_books.key');

        if (empty($key)) {
            throw new RuntimeException('Google Books API key is not configured.');
        }

        $this->apiKey = $key;
    }

    public function search(string $query, array $options = []): array
    {
        $options = array_merge($this->defaultSearchOptions(), $options);

        $params = array_filter([
            'q' => $query,
            'printType' => PrintType::BOOKS->value,
            'maxResults' => min((int) $options['perPage'], self::MAX_RESULTS_CAP),
            'orderBy' => $options['orderBy'],
            'key' => $this->apiKey,
            'langRestrict' => $options['langRestrict'] ?? null,
        ], fn ($v) => $v !== null);

        try {
            $response = $this->get(self::BASE_URL, $params);
            $data = $response->json();

            return [
                'success' => true,
                'total_items' => (int) ($data['totalItems'] ?? 0),
                'per_page' => $options['perPage'],
                'items' => $data['items'] ?? [],
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'items' => [], 'total_items' => 0, 'per_page' => $options['perPage']];
        }
    }

    public function getVolume(string $volumeId): array
    {
        $cacheKey = "google_books_volume_{$volumeId}";

        if (Cache::has($cacheKey)) {
            return ['success' => true, 'item' => Cache::get($cacheKey)];
        }

        try {
            $response = $this->get(self::BASE_URL."/{$volumeId}");
            $rawItem = $response->json();

            Cache::put($cacheKey, $rawItem, now()->addDays(self::CACHE_TTL_DAYS));

            return ['success' => true, 'item' => $rawItem];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function get(string $url, array $query = []): Response
    {
        $response = Http::timeout(self::REQUEST_TIMEOUT)->get($url, array_merge($query, ['key' => $this->apiKey]));

        if (! $response->successful()) {
            Log::warning('Google Books API error', ['status' => $response->status(), 'url' => $url]);
            $response->throw();
        }

        return $response;
    }

    private function defaultSearchOptions(): array
    {
        return [
            'langRestrict' => null,
            'perPage' => 20,
            'orderBy' => OrderBy::RELEVANCE->value,
        ];
    }

    public static function getSupportedLanguages(): array
    {
        return ['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'ku' => 'Kurdî', 'tr' => 'Türkçe', 'ru' => 'Русский', 'de' => 'Deutsch', 'es' => 'Español'];
    }
}
