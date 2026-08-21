<?php

namespace App\Repositories\Contracts\Borrow;

use App\Enums\BorrowStatus;
use App\Models\Borrow;
use App\Models\Fine;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface BorrowRepositoryInterface
{
    public function getAllBorrows(array $filters, int $perPage): LengthAwarePaginator;

    public function getUserBorrows(User $user, array $filters, int $perPage): LengthAwarePaginator;

    public function findById(int $id): ?Borrow;

    public function create(array $data): Borrow;

    public function updateStatus(Borrow $borrow, BorrowStatus $status, array $extra = []): Borrow;

    public function hasActiveBorrow(int $userId, int $bookId): bool;

    public function createFine(Borrow $borrow, float $amount): Fine;

    public function getUserFines(User $user, int $perPage): LengthAwarePaginator;

    public function markFinePaid(Fine $fine): Fine;
}
