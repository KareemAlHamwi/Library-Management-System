<?php

namespace App\Services\Author;

use App\Models\Author;
use App\Repositories\Contracts\Author\AuthorRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AuthorsService
{
    public function __construct(private readonly AuthorRepositoryInterface $authorRepository) {}

    public function list(int $perPage): LengthAwarePaginator
    {
        return $this->authorRepository->paginate($perPage);
    }

    public function get(int $id): ?Author
    {
        return $this->authorRepository->findById($id);
    }

    public function add(array $data): Author
    {
        return $this->authorRepository->create($data);
    }

    public function update(Author $author, array $data): Author
    {
        return $this->authorRepository->update($author, $data);
    }

    public function delete(Author $author): void
    {
        $this->authorRepository->delete($author);
    }
}
