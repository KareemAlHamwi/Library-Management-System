<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
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
}
