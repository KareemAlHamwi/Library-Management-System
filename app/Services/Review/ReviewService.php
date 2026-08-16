<?php

namespace App\Services\Review;

use App\Models\Book;
use App\Models\Borrow;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\User;
use App\Repositories\Contracts\Review\ReviewRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ReviewService
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviewRepository
    ) {}

    public function getBookReviews(Book $book, int $perPage): LengthAwarePaginator
    {
        return $this->reviewRepository->getBookReviews($book, $perPage);
    }

    public function getAllReviews(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->reviewRepository->getAllReviews($filters, $perPage);
    }

    public function getUserReviews(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->reviewRepository->getUserReviews($user, $perPage);
    }

    public function add(User $user, Book $book, array $data): Review
    {
        if ($this->reviewRepository->hasReviewed($user, $book)) {
            throw new \Exception('You have already reviewed this book.', 409);
        }

        if (! $this->hasInteractedWithBook($user, $book)) {
            throw new \Exception('You can only review books you have borrowed or purchased.', 403);
        }

        $review = $this->reviewRepository->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return $review;
    }

    public function update(User $user, Review $review, array $data): Review
    {
        if ($review->user_id !== $user->id) {
            throw new \Exception('You are not authorized to update this review.', 403);
        }

        return $this->reviewRepository->update($review, [
            'rating' => $data['rating'] ?? $review->rating,
            'comment' => $data['comment'] ?? $review->comment,
            'is_approved' => false,
        ]);
    }

    public function delete(User $user, Review $review): void
    {
        if ($review->user_id !== $user->id) {
            throw new \Exception('You are not authorized to delete this review.', 403);
        }

        $this->reviewRepository->delete($review);
    }

    public function approve(Review $review): Review
    {
        $review = $this->reviewRepository->approve($review);
        $this->syncBookRating($review->book_id);

        return $review;
    }

    public function adminDelete(Review $review): void
    {
        $bookId = $review->book_id;
        $this->reviewRepository->delete($review);
        $this->syncBookRating($bookId);
    }

    private function hasInteractedWithBook(User $user, Book $book): bool
    {
        $hasPurchased = Purchase::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->exists();

        if ($hasPurchased) {
            return true;
        }

        return Borrow::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->whereIn('status', ['active', 'returned'])
            ->exists();
    }

    private function syncBookRating(int $bookId): void
    {
        $approved = Review::where('book_id', $bookId)
            ->where('is_approved', true);

        $count = $approved->count();
        $rating = $count > 0 ? round($approved->avg('rating'), 2) : 0;

        Book::where('id', $bookId)->update([
            'overall_rating' => $rating,
            'reviewers_count' => $count,
        ]);
    }
}
