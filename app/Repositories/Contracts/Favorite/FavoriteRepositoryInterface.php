<?php

namespace App\Repositories\Contracts\Favorite;

use App\Models\Book;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface FavoriteRepositoryInterface
{
    public function getUserFavorites(User $user, int $perPage): LengthAwarePaginator;

    public function isFavorited(User $user, Book $book): bool;

    public function add(User $user, Book $book): void;

    public function remove(User $user, Book $book): void;
}
