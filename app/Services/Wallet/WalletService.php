<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\TopUpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class WalletService
{

    public function createWallet(User $user, string $currency = 'SYP'): Wallet
    {
        return Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'currency' => $currency
        ]);
    }


    public function getOrCreateWallet(User $user): Wallet
    {
        return $user->wallet ?? $this->createWallet($user);
    }


    public function deposit(Wallet $wallet, float $amount, string $referenceType = null, int $referenceId = null): Transaction
    {
        if ($amount <= 0) {
            throw new \Exception('The amount must be greater than zero.');
        }

        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId) {
            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => 'credit',
                'status' => 'completed',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId
            ]);

            $wallet->increment('balance', $amount);

            return $transaction;
        });
    }


    public function withdraw(Wallet $wallet, float $amount, string $referenceType = null, int $referenceId = null): Transaction
    {
        if ($amount <= 0) {
            throw new \Exception('The amount must be greater than zero.');
        }

        if ($wallet->balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId) {
            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => 'debit',
                'status' => 'completed',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId
            ]);

            $wallet->decrement('balance', $amount);

            return $transaction;
        });
    }


    public function requestTopUp(User $user, float $amount, string $paymentMethod = null): TopUpRequest
    {
        if ($amount <= 0) {
            throw new \Exception('The amount must be greater than zero.');
        }

        $pendingRequest = TopUpRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            throw new \Exception('You currently have a top-up request being processed. Please wait until it is approved or rejected.');
        }

        $request = TopUpRequest::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_method' => $paymentMethod ?? 'bank_transfer',
            'status' => 'pending',
            'requested_at' => now()
        ]);

        return $request;
    }


    public function approveTopUp(TopUpRequest $topUpRequest): Transaction
    {
        if ($topUpRequest->status !== 'pending') {
            throw new \Exception('This request has already been processed.');
        }

        $user = $topUpRequest->user;
        $amount = $topUpRequest->amount;

        return DB::transaction(function () use ($user, $amount, $topUpRequest) {
            $wallet = $this->getOrCreateWallet($user);
            $transaction = $this->deposit(
                $wallet,
                $amount,
                'top_up_approved',
                $topUpRequest->id
            );

            $topUpRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id()
            ]);

            return $transaction;
        });
    }


    public function rejectTopUp(TopUpRequest $topUpRequest, string $reason = null): void
    {
        if ($topUpRequest->status !== 'pending') {
            throw new \Exception('This request has already been processed.');
        }

        $topUpRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_reason' => $reason
        ]);
    }


    public function getUserTopUpRequests(User $user)
    {
        return TopUpRequest::where('user_id', $user->id)
            ->orderBy('requested_at', 'desc')
            ->get();
    }


    public function getAllTopUpRequests()
    {
        return TopUpRequest::with('user')
            ->orderBy('requested_at', 'desc')
            ->get();
    }

    public function hasPendingTopUpRequest(User $user): bool
    {
        return TopUpRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }


    public function getPendingTopUpRequest(User $user): ?TopUpRequest
    {
        return TopUpRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();
    }

    public function getBalance(Wallet $wallet): float
    {
        return $wallet->balance;
    }

    public function getTransactions(Wallet $wallet, int $limit = 50)
    {
        return $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }


    public function getTransactionsByType(Wallet $wallet, string $type, int $limit = 50)
    {
        return $wallet->transactions()
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }


    public function hasSufficientBalance(Wallet $wallet, float $amount): bool
    {
        return $wallet->balance >= $amount;
    }


    public function depositFromReward(Wallet $wallet, float $amount, int $rewardTransactionId): Transaction
    {
        return $this->deposit($wallet, $amount, 'reward', $rewardTransactionId);
    }


    public function withdrawForPurchase(Wallet $wallet, float $amount, int $purchaseId): Transaction
    {
        return $this->withdraw($wallet, $amount, 'purchase', $purchaseId);
    }
}
