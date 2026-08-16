<?php

namespace App\Repositories\Eloquent\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use App\Repositories\Contracts\Review\ReviewRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ReviewRepository implements ReviewRepositoryInterface
{
    public function getBookReviews(Book $book, int $perPage): LengthAwarePaginator
    {
        return Review::query()
            ->where('book_id', $book->id)
            ->where('is_approved', true)
            ->with('user')
            ->latest()
            ->paginate($perPage);
    }

    public function getAllReviews(array $filters, int $perPage): LengthAwarePaginator
    {
        return Review::query()
            ->when(
                isset($filters['is_approved']),
                fn ($q) => $q->where('is_approved', $filters['is_approved'])
            )
            ->when(
                $filters['book_id'] ?? null,
                fn ($q, $v) => $q->where('book_id', $v)
            )
            ->with(['user', 'book'])
            ->latest()
            ->paginate($perPage);
    }

    public function getUserReviews(User $user, int $perPage): LengthAwarePaginator
    {
        return Review::query()
            ->where('user_id', $user->id)
            ->with('book')
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Review
    {
        return Review::with(['user', 'book'])->find($id);
    }

    public function hasReviewed(User $user, Book $book): bool
    {
        return Review::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->exists();
    }

    public function create(array $data): Review
    {
        return Review::create($data);
    }

    public function update(Review $review, array $data): Review
    {
        $review->update($data);

        return $review->fresh();
    }

    public function delete(Review $review): void
    {
        $review->delete();
    }

    public function approve(Review $review): Review
    {
        $review->update(['is_approved' => true]);

        return $review->fresh();
    }
}
