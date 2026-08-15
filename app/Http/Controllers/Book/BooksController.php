<?php

namespace App\Http\Controllers\Book;

use App\Http\Controllers\Controller;
use App\Http\Requests\Book\AddBookRequest;
use App\Http\Requests\Book\ListBooksRequest;
use App\Http\Requests\Book\UpdateBookRequest;
use App\Http\Resources\Book\BookListResource;
use App\Http\Resources\Book\BookResource;
use App\Models\Book;
use App\Models\User;
use App\Models\Category;
use App\Notifications\BookNotification;
use App\Services\Book\BooksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Notification;

class BooksController extends Controller
{
    public function __construct(private readonly BooksService $booksService) {}

    public function list(ListBooksRequest $request): AnonymousResourceCollection
    {
        return BookListResource::collection(
            $this->booksService->list($request->validated())
        );
    }

    public function newArrivals(Request $request): AnonymousResourceCollection
    {
        return BookListResource::collection(
            $this->booksService->newArrivals((int) $request->input('per_page', 15))
        );
    }

    public function popular(Request $request): AnonymousResourceCollection
    {
        return BookListResource::collection(
            $this->booksService->popular((int) $request->input('per_page', 15))
        );
    }

    public function recommended(Request $request): AnonymousResourceCollection
    {
        return BookListResource::collection(
            $this->booksService->recommended(auth()->id(), (int) $request->input('per_page', 15))
        );
    }

    public function get(int $bookId): JsonResponse
    {
        $book = $this->booksService->get($bookId);

        if (! $book) {
            return response()->json(['message' => __('books.book_not_found')], 404);
        }

        return response()->json(new BookResource($book));
    }

    public function add(AddBookRequest $request): JsonResponse
    {
        $book = $this->booksService->add($request->validated());
        $users=User::all();
        Notification::send($users,new BookNotification($book));
        return response()->json(new BookResource($book), 201);
    }

    public function update(UpdateBookRequest $request, int $bookId): JsonResponse
    {
        $book = $this->booksService->get($bookId);

        if (! $book) {
            return response()->json(['message' => __('books.book_not_found')], 404);
        }

        return response()->json(new BookResource(
            $this->booksService->update($book, $request->validated())
        ));
    }

    public function delete(int $authorId): JsonResponse
    {
        $author = $this->booksService->get($authorId);

        if (! $author) {
            return response()->json(['message' => __('books.book_not_found')], 404);
        }

        $this->booksService->delete($author);

        return response()->json(['message' => __('books.book_deleted')]);
    }
}
