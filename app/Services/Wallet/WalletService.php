<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * إنشاء محفظة للمستخدم الجديد
     */
    public function createWallet(User $user, string $currency = 'SYP'): Wallet
    {
        return Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'currency' => $currency
        ]);
    }

    /**
     * الحصول على محفظة المستخدم (أو إنشاؤها إذا لم توجد)
     */
    public function getOrCreateWallet(User $user): Wallet
    {
        return $user->wallet ?? $this->createWallet($user);
    }

    /**
     * إضافة رصيد للمحفظة (إيداع)
     */
    public function deposit(Wallet $wallet, float $amount, string $referenceType = null, int $referenceId = null): Transaction
    {
        if ($amount <= 0) {
            throw new \Exception('المبلغ يجب أن يكون أكبر من صفر');
        }

        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId) {
            // إنشاء المعاملة
            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => 'credit',
                'status' => 'completed',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId
            ]);

            // تحديث الرصيد
            $wallet->increment('balance', $amount);

            return $transaction;
        });
    }

    /**
     * سحب رصيد من المحفظة
     */
    public function withdraw(Wallet $wallet, float $amount, string $referenceType = null, int $referenceId = null): Transaction
    {
        if ($amount <= 0) {
            throw new \Exception('المبلغ يجب أن يكون أكبر من صفر');
        }

        // التحقق من الرصيد
        if ($wallet->balance < $amount) {
            throw new \Exception('الرصيد غير كافٍ');
        }

        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId) {
            // إنشاء المعاملة
            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => 'debit',
                'status' => 'completed',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId
            ]);

            // تحديث الرصيد
            $wallet->decrement('balance', $amount);

            return $transaction;
        });
    }

    /**
     * الحصول على رصيد المحفظة
     */
    public function getBalance(Wallet $wallet): float
    {
        return $wallet->balance;
    }

    /**
     * الحصول على تاريخ المعاملات
     */
    public function getTransactions(Wallet $wallet, int $limit = 50)
    {
        return $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على معاملات محددة النوع
     */
    public function getTransactionsByType(Wallet $wallet, string $type, int $limit = 50)
    {
        return $wallet->transactions()
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * التحقق من كفاية الرصيد
     */
    public function hasSufficientBalance(Wallet $wallet, float $amount): bool
    {
        return $wallet->balance >= $amount;
    }

    /**
     * إضافة رصيد كمكافأة (من Reward Transactions)
     */
    public function depositFromReward(Wallet $wallet, float $amount, int $rewardTransactionId): Transaction
    {
        return $this->deposit($wallet, $amount, 'reward', $rewardTransactionId);
    }

    /**
     * سحب رصيد للشراء (من Purchases)
     */
    public function withdrawForPurchase(Wallet $wallet, float $amount, int $purchaseId): Transaction
    {
        return $this->withdraw($wallet, $amount, 'purchase', $purchaseId);
    }
}
