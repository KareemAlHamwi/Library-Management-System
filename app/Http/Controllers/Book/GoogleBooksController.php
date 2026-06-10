<?php

namespace App\Http\Controllers\Book;

use App\Http\Controllers\Controller;
use App\Http\Requests\Book\SearchGoogleBooksRequest;
use App\Http\Resources\Book\GoogleBookDetailedResource;
use App\Http\Resources\Book\GoogleBookResource;
use App\Services\Book\GoogleBooksService;
use App\Services\Book\OrderBy;
use Illuminate\Http\JsonResponse;

class GoogleBooksController extends Controller
{
    public function __construct(private readonly GoogleBooksService $googleBooksService) {}

    public function search(SearchGoogleBooksRequest $request): JsonResponse
    {
        $results = $this->googleBooksService->search(
            $request->input('q'),
            [
                'langRestrict' => $request->input('lang'),
                'perPage' => $request->integer('per_page', 20),
                'orderBy' => $request->input('order_by', OrderBy::RELEVANCE->value),
            ]
        );

        if (! $results['success']) {
            return response()->json(['message' => $results['error']], 502);
        }

        return response()->json([
            'data' => [
                'total_items' => $results['total_items'],
                'per_page' => $results['per_page'],
                'items' => GoogleBookResource::collection($results['items']),
            ],
        ]);
    }

    public function getVolume(string $volumeId): JsonResponse
    {
        $volume = $this->googleBooksService->getVolume($volumeId);

        if (! $volume['success']) {
            $status = str_contains($volume['error'] ?? '', 'not found') ? 404 : 502;

            return response()->json(['message' => $volume['error']], $status);
        }

        return response()->json([
            'data' => new GoogleBookDetailedResource($volume['item']),
        ]);
    }
}
