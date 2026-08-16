<?php

namespace App\Repositories\Contracts\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReviewRepositoryInterface
{
    public function getBookReviews(Book $book, int $perPage): LengthAwarePaginator;

    public function getAllReviews(array $filters, int $perPage): LengthAwarePaginator;

    public function getUserReviews(User $user, int $perPage): LengthAwarePaginator;

    public function findById(int $id): ?Review;

    public function hasReviewed(User $user, Book $book): bool;

    public function create(array $data): Review;

    public function update(Review $review, array $data): Review;

    public function delete(Review $review): void;

    public function approve(Review $review): Review;
}
