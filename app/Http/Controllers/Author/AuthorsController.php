<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Http\Requests\Author\AddAuthorRequest;
use App\Http\Requests\Author\UpdateAuthorRequest;
use App\Http\Resources\Author\AuthorListResource;
use App\Http\Resources\Author\AuthorResource;
use App\Services\Author\AuthorsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuthorsController extends Controller
{
    public function __construct(private readonly AuthorsService $authorsService) {}

    public function list(Request $request): AnonymousResourceCollection
    {
        return AuthorListResource::collection(
            $this->authorsService->list((int) $request->input('per_page', 15))
        );
    }

    public function get(int $authorId): JsonResponse
    {
        $author = $this->authorsService->get($authorId);

        if (! $author) {
            return response()->json(['message' => __('author.author_not_found')], 404);
        }

        return response()->json(new AuthorResource($author));
    }

    public function add(AddAuthorRequest $request): JsonResponse
    {
        return response()->json(
            new AuthorResource($this->authorsService->add($request->validated())),
            201
        );
    }

    public function update(UpdateAuthorRequest $request, int $authorId): JsonResponse
    {
        $author = $this->authorsService->get($authorId);

        if (! $author) {
            return response()->json(['message' => __('author.author_not_found')], 404);
        }

        return response()->json(
            new AuthorResource($this->authorsService->update($author, $request->validated()))
        );
    }

    public function delete(int $authorId): JsonResponse
    {
        $author = $this->authorsService->get($authorId);

        if (! $author) {
            return response()->json(['message' => __('author.author_not_found')], 404);
        }

        $this->authorsService->delete($author);

        return response()->json(['message' => __('author.author_deleted')]);
    }
}
