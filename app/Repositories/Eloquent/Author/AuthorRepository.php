<?php

namespace App\Repositories\Eloquent\Author;

use App\Models\Author;
use App\Repositories\Contracts\Author\AuthorRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AuthorRepository implements AuthorRepositoryInterface
{
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Author::query()
            ->withCount('books')
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Author
    {
        return Author::with('books')->find($id);
    }

    public function create(array $data): Author
    {
        return Author::create($data);
    }

    public function update(Author $author, array $data): Author
    {
        $author->update($data);

        return $author->fresh();
    }

    public function delete(Author $author): void
    {
        $author->delete();
    }
}
