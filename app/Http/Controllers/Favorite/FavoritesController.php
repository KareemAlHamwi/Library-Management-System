<?php

namespace App\Http\Controllers\Favorite;

use App\Http\Controllers\Controller;
use App\Http\Resources\Book\BookListResource;
use App\Models\Book;
use App\Services\Favorite\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoritesController extends Controller
{
    public function __construct(
        private readonly FavoriteService $favoriteService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return BookListResource::collection(
            $this->favoriteService->getUserFavorites(
                $request->user(),
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function check(Request $request, Book $book): JsonResponse
    {
        return response()->json([
            'favorited' => $this->favoriteService->isFavorited($request->user(), $book),
        ]);
    }

    public function toggle(Request $request, Book $book): JsonResponse
    {
        $result = $this->favoriteService->toggle($request->user(), $book);

        return response()->json($result);
    }
}
