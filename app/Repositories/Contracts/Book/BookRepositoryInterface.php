<?php

namespace App\Repositories\Contracts\Book;

use App\Models\Book;
use Illuminate\Pagination\LengthAwarePaginator;

interface BookRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function newArrivals(int $perPage): LengthAwarePaginator;

    public function popular(int $perPage): LengthAwarePaginator;

    public function recommended(int $id, int $perPage): LengthAwarePaginator;

    public function findById(int $id): ?Book;

    public function create(array $data): Book;

    public function update(Book $book, array $data): Book;
}
