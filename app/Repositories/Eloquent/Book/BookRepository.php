<?php

namespace App\Repositories\Eloquent\Book;

use App\Models\Book;
use App\Models\Borrow;
use App\Repositories\Contracts\Book\BookRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class BookRepository implements BookRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return Book::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('title', 'like', "%$s%")
                ->orWhere('isbn', 'like', "%$s%")
            )
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->whereHas('categories', fn ($q) => $q->where('categories.id', $v))
            )
            ->when($filters['author_id'] ?? null, fn ($q, $v) => $q->whereHas('authors', fn ($q) => $q->where('authors.id', $v))
            )
            ->with(['authors', 'categories'])
            ->latest()
            ->paginate($perPage);
    }

    public function newArrivals(int $perPage): LengthAwarePaginator
    {
        return Book::query()
            ->with(['authors', 'categories'])
            ->latest()
            ->paginate($perPage);
    }

    public function popular(int $perPage): LengthAwarePaginator
    {
        return Book::query()
            ->with(['authors', 'categories'])
            ->orderByDesc('reviewers_count')
            ->orderByDesc('overall_rating')
            ->paginate($perPage);
    }

    public function recommended(int $userId, int $perPage): LengthAwarePaginator
    {
        $categoryIds = Borrow::query()
            ->where('user_id', $userId)
            ->with('book.categories')
            ->get()
            ->pluck('book.categories')->flatten()
            ->pluck('id')
            ->unique();

        if ($categoryIds->isEmpty()) {
            return $this->popular($perPage);
        }

        return Book::query()
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
            ->with(['authors', 'categories'])
            ->orderByDesc('overall_rating')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Book
    {
        return Book::with(['authors', 'categories'])->find($id);
    }

    public function create(array $data): Book
    {
        return Book::create($data);
    }

    public function update(Book $book, array $data): Book
    {
        $book->update($data);

        return $book->fresh(['authors', 'categories']);
    }
}
