<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * عرض معلومات المحفظة
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        return response()->json([
            'wallet' => [
                'id' => $wallet->id,
                'balance' => $wallet->balance,
                'currency' => $wallet->currency,
                'created_at' => $wallet->created_at,
                'updated_at' => $wallet->updated_at
            ]
        ]);
    }

    /**
     * عرض تاريخ المعاملات
     */
    public function transactions(): JsonResponse
    {
        $user = Auth::user();
        $wallet = $this->walletService->getOrCreateWallet($user);
        $transactions = $this->walletService->getTransactions($wallet);

        return response()->json([
            'transactions' => $transactions
        ]);
    }

    /**
     * عرض المعاملات حسب النوع
     */
    public function transactionsByType(string $type): JsonResponse
    {
        $user = Auth::user();
        $wallet = $this->walletService->getOrCreateWallet($user);
        $transactions = $this->walletService->getTransactionsByType($wallet, $type);

        return response()->json([
            'transactions' => $transactions
        ]);
    }

    /**
     * عرض الرصيد الحالي
     */
    public function balance(): JsonResponse
    {
        $user = Auth::user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        return response()->json([
            'balance' => $wallet->balance,
            'currency' => $wallet->currency
        ]);
    }

    public function deposit(Request $request): JsonResponse
    {
        try {
            // 1. الحصول على المستخدم الحالي
            $user = Auth::user();

            // 2. الحصول على محفظة المستخدم (أو إنشاؤها)
            $wallet = $this->walletService->getOrCreateWallet($user);

            // 3. التحقق من صحة البيانات
            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'nullable|in:SYP,USD' // اختياري
            ]);

            // 4. ✅ استخدام دالة deposit من الـ Service
            $transaction = $this->walletService->deposit(
                $wallet,
                $request->amount,
                'manual_deposit',  // نوع المرجع
                null               // معرف المرجع
            );

            // 5. إرجاع الرد
            return response()->json([
                'message' => 'تم شحن المحفظة بنجاح',
                'amount' => $request->amount,
                'new_balance' => $wallet->fresh()->balance,
                'currency' => $wallet->currency,
                'transaction' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
