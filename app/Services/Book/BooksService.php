<?php

namespace App\Services\Book;

use App\Models\Book;
use App\Repositories\Contracts\Book\BookRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class BooksService
{
    public function __construct(private readonly BookRepositoryInterface $bookRepository) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return $this->bookRepository->paginate($filters, $perPage);
    }

    public function newArrivals(int $perPage): LengthAwarePaginator
    {
        return $this->bookRepository->newArrivals($perPage);
    }

    public function popular(int $perPage): LengthAwarePaginator
    {
        return $this->bookRepository->popular($perPage);
    }

    public function recommended(int $userId, int $perPage): LengthAwarePaginator
    {
        return $this->bookRepository->recommended($userId, $perPage);
    }

    public function get(int $id): ?Book
    {
        return $this->bookRepository->findById($id);
    }

    public function add(array $data): Book
    {
        $data['cover_image'] = isset($data['cover_image'])
            ? Storage::disk('public')->put('books/covers', $data['cover_image'])
            : null;

        $book = $this->bookRepository->create(
            collect($data)->except(['author_ids', 'category_ids'])->toArray()
        );

        if (! empty($data['author_ids'])) {
            $book->authors()->sync($data['author_ids']);
        }
        if (! empty($data['category_ids'])) {
            $book->categories()->sync($data['category_ids']);
        }

        return $book->load(['authors', 'categories']);
    }

    public function update(Book $book, array $data): Book
    {
        if (isset($data['cover_image'])) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $data['cover_image'] = Storage::disk('public')->put('books/covers', $data['cover_image']);
        }

        $this->bookRepository->update(
            $book,
            collect($data)->except(['author_ids', 'category_ids'])->toArray()
        );

        if (array_key_exists('author_ids', $data)) {
            $book->authors()->sync($data['author_ids'] ?? []);
        }
        if (array_key_exists('category_ids', $data)) {
            $book->categories()->sync($data['category_ids'] ?? []);
        }

        return $book->load(['authors', 'categories']);
    }
}
