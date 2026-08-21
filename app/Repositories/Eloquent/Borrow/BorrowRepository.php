<?php

namespace App\Repositories\Eloquent\Borrow;

use App\Enums\BorrowStatus;
use App\Models\Borrow;
use App\Models\Fine;
use App\Models\User;
use App\Repositories\Contracts\Borrow\BorrowRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class BorrowRepository implements BorrowRepositoryInterface
{
    public function getAllBorrows(array $filters, int $perPage): LengthAwarePaginator
    {
        return Borrow::query()
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['book_id'] ?? null, fn ($q, $v) => $q->where('book_id', $v))
            ->with(['user', 'book', 'fine'])
            ->latest()
            ->paginate($perPage);
    }

    public function getUserBorrows(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return Borrow::query()
            ->where('user_id', $user->id)
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->with(['book', 'fine'])
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Borrow
    {
        return Borrow::with(['user', 'book', 'fine'])->find($id);
    }

    public function create(array $data): Borrow
    {
        return Borrow::create($data);
    }

    public function updateStatus(Borrow $borrow, BorrowStatus $status, array $extra = []): Borrow
    {
        $borrow->update(array_merge(['status' => $status], $extra));

        return $borrow->fresh(['user', 'book', 'fine']);
    }

    public function hasActiveBorrow(int $userId, int $bookId): bool
    {
        return Borrow::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->whereIn('status', [BorrowStatus::PENDING, BorrowStatus::ACTIVE])
            ->exists();
    }

    public function createFine(Borrow $borrow, float $amount): Fine
    {
        return Fine::create([
            'borrow_id' => $borrow->id,
            'amount' => $amount,
        ]);
    }

    public function getUserFines(User $user, int $perPage): LengthAwarePaginator
    {
        return Fine::query()
            ->whereHas('borrow', fn ($q) => $q->where('user_id', $user->id))
            ->with(['borrow.book'])
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function markFinePaid(Fine $fine): Fine
    {
        $fine->update(['paid_at' => now()]);

        return $fine->fresh();
    }
}
