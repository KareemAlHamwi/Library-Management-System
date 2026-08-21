<?php

namespace App\Services\Borrow;

use App\Enums\BorrowStatus;
use App\Models\Book;
use App\Models\Borrow;
use App\Models\Fine;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\Contracts\Borrow\BorrowRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BorrowService
{
    const FINE_RATE_PER_DAY = 0.5;

    public function __construct(
        private readonly BorrowRepositoryInterface $borrowRepository
    ) {}

    public function getAllBorrows(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->borrowRepository->getAllBorrows($filters, $perPage);
    }

    public function getUserBorrows(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->borrowRepository->getUserBorrows($user, $filters, $perPage);
    }

    public function getById(int $id): ?Borrow
    {
        return $this->borrowRepository->findById($id);
    }

    public function getUserFines(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->borrowRepository->getUserFines($user, $perPage);
    }

    public function request(User $user, Book $book, string $dueDate): Borrow
    {
        if ($book->available_copies < 1) {
            throw new \Exception('No available copies for this book.', 422);
        }

        if ($this->borrowRepository->hasActiveBorrow($user->id, $book->id)) {
            throw new \Exception('You already have an active or pending borrow for this book.', 409);
        }

        $borrow = DB::transaction(function () use ($user, $book, $dueDate) {
            return $this->borrowRepository->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'due_date' => $dueDate,
                'status' => BorrowStatus::PENDING,
            ]);
        }, 3);

        $borrow->loadMissing(['user', 'book']);

        return $borrow;
    }

    public function approve(Borrow $borrow): Borrow
    {
        if ($borrow->status !== BorrowStatus::PENDING) {
            throw new \Exception('Only pending borrows can be approved.', 422);
        }

        $borrow = DB::transaction(function () use ($borrow) {
            $borrow->book->decrement('available_copies');

            return $this->borrowRepository->updateStatus($borrow, BorrowStatus::ACTIVE);
        }, 3);

        $borrow->loadMissing(['user', 'book', 'fine']);

        return $borrow;
    }

    public function reject(Borrow $borrow): Borrow
    {
        if ($borrow->status !== BorrowStatus::PENDING) {
            throw new \Exception('Only pending borrows can be rejected.', 422);
        }

        $borrow = $this->borrowRepository->updateStatus($borrow, BorrowStatus::REJECTED);

        $borrow->loadMissing(['user', 'book']);

        return $borrow;
    }

    public function markReturned(Borrow $borrow): Borrow
    {
        if (! in_array($borrow->status, [BorrowStatus::ACTIVE, BorrowStatus::OVERDUE])) {
            throw new \Exception('Only active or overdue borrows can be returned.', 422);
        }

        $borrow = DB::transaction(function () use ($borrow) {
            $borrow->book->increment('available_copies');

            return $this->borrowRepository->updateStatus(
                $borrow,
                BorrowStatus::RETURNED,
                ['returned_at' => now()]
            );
        }, 3);

        $borrow->loadMissing(['user', 'book', 'fine']);

        return $borrow;
    }

    public function markOverdue(Borrow $borrow): Borrow
    {
        if ($borrow->status !== BorrowStatus::ACTIVE) {
            throw new \Exception('Only active borrows can be marked overdue.', 422);
        }

        if (! $borrow->due_date->isPast()) {
            throw new \Exception('Due date has not passed yet.', 422);
        }

        $borrow = DB::transaction(function () use ($borrow) {
            $updated = $this->borrowRepository->updateStatus($borrow, BorrowStatus::OVERDUE);
            $daysOverdue = now()->diffInDays($borrow->due_date);
            $amount = max(1, $daysOverdue) * self::FINE_RATE_PER_DAY;
            $this->borrowRepository->createFine($updated, $amount);

            return $updated;
        }, 3);

        $borrow->loadMissing(['user', 'book', 'fine']);

        return $borrow;
    }

    public function payFine(User $user, Fine $fine): Fine
    {
        if ($fine->isPaid()) {
            throw new \Exception('This fine has already been paid.', 409);
        }

        $fine->loadMissing('borrow');

        if ($fine->borrow->user_id !== $user->id) {
            throw new \Exception('You are not authorized to pay this fine.', 403);
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        if (! $wallet || $wallet->balance < $fine->amount) {
            throw new \Exception('Insufficient wallet balance.', 422);
        }

        $fine = DB::transaction(function () use ($wallet, $fine) {
            $wallet->decrement('balance', $fine->amount);

            return $this->borrowRepository->markFinePaid($fine);
        }, 3);

        $fine->loadMissing(['borrow.book']);

        return $fine;
    }
}
