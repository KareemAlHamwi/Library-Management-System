<?php

namespace App\Repositories\Eloquent\Favorite;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use App\Repositories\Contracts\Favorite\FavoriteRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class FavoriteRepository implements FavoriteRepositoryInterface
{
    public function getUserFavorites(User $user, int $perPage): LengthAwarePaginator
    {
        return Book::query()
            ->whereHas('favorites', fn ($q) => $q->where('user_id', $user->id))
            ->with(['authors', 'categories'])
            ->latest()
            ->paginate($perPage);
    }

    public function isFavorited(User $user, Book $book): bool
    {
        return Favorite::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->exists();
    }

    public function add(User $user, Book $book): void
    {
        Favorite::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function remove(User $user, Book $book): void
    {
        Favorite::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->delete();
    }
}
