<?php

namespace App\Services\Reward;

use App\Models\User;
use App\Models\RewardTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

class RewardTransactionService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * إضافة نقاط للمستخدم
     */
    public function addPoints(User $user, int $points, string $reason, int $referenceId = null): RewardTransaction
    {
        if ($points <= 0) {
            throw new \Exception('النقاط يجب أن تكون أكبر من صفر');
        }

        return DB::transaction(function () use ($user, $points, $reason, $referenceId) {
            // 1. إنشاء سجل النقاط
            $reward = RewardTransaction::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'earned',
                'reason' => $reason,
                'reference_id' => $referenceId
            ]);

            // 2. تحديث نقاط المستخدم
            $user->increment('purchase_points', $points);

            return $reward;
        });
    }

    /**
     * صرف نقاط (تحويلها إلى رصيد في المحفظة)
     */
    public function redeemPoints(User $user, int $points): void
    {
        // التحقق من وجود نقاط كافية
        if ($user->purchase_points < $points) {
            throw new \Exception('النقاط غير كافية');
        }

        // التحقق من أن النقاط قابلة للصرف (مثلاً 10 نقاط = 1 دولار)
        $conversionRate = 10; // 10 نقاط = 1 دولار
        $amount = $points / $conversionRate;

        DB::transaction(function () use ($user, $points, $amount) {
            // 1. إنشاء سجل صرف النقاط
            $reward = RewardTransaction::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'spent',
                'reason' => 'تحويل نقاط إلى رصيد'
            ]);

            // 2. خصم النقاط من المستخدم
            $user->decrement('purchase_points', $points);

            // 3. إضافة رصيد للمحفظة
            $wallet = $this->walletService->getOrCreateWallet($user);
            $this->walletService->depositFromReward($wallet, $amount, $reward->id);
        });
    }

    /**
     * الحصول على رصيد النقاط
     */
    public function getPointsBalance(User $user): int
    {
        return $user->purchase_points;
    }

    /**
     * الحصول على تاريخ النقاط
     */
    public function getTransactionHistory(User $user, int $limit = 50)
    {
        return RewardTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
