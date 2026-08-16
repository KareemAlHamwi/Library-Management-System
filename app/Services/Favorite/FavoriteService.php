<?php

namespace App\Services\Favorite;

use App\Models\Book;
use App\Models\User;
use App\Repositories\Contracts\Favorite\FavoriteRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class FavoriteService
{
    public function __construct(
        private readonly FavoriteRepositoryInterface $favoriteRepository
    ) {}

    public function getUserFavorites(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->favoriteRepository->getUserFavorites($user, $perPage);
    }

    public function isFavorited(User $user, Book $book): bool
    {
        return $this->favoriteRepository->isFavorited($user, $book);
    }

    public function toggle(User $user, Book $book): array
    {
        if ($this->favoriteRepository->isFavorited($user, $book)) {
            $this->favoriteRepository->remove($user, $book);
            return ['favorited' => false, 'message' => 'Book removed from favorites.'];
        }

        $this->favoriteRepository->add($user, $book);
        return ['favorited' => true, 'message' => 'Book added to favorites.'];
    }
}
