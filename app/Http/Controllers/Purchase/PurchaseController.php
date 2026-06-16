<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\Cart\CartService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    protected CartService $cartService;
    protected WalletService $walletService;

    public function __construct(CartService $cartService, WalletService $walletService)
    {
        $this->cartService = $cartService;
        $this->walletService = $walletService;
    }

    /**
     * إتمام عملية الشراء من السلة
     */
    public function checkout(): JsonResponse
    {
        try {
            $user = Auth::user();
            $cartItems = $this->cartService->getCartItems($user);

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'message' => 'السلة فارغة'
                ], 400);
            }

            // حساب الإجمالي
            $total = $this->cartService->getCartTotal($user);

            // الحصول على المحفظة
            $wallet = $this->walletService->getOrCreateWallet($user);

            // التحقق من الرصيد
            if (!$this->walletService->hasSufficientBalance($wallet, $total)) {
                return response()->json([
                    'message' => 'الرصيد غير كافٍ',
                    'balance' => $wallet->balance,
                    'required' => $total
                ], 400);
            }

            // تنفيذ الشراء في ترانزاكشن
            DB::transaction(function () use ($user, $cartItems, $total, $wallet) {
                // 1. إنشاء معاملة السحب
                $transaction = $this->walletService->withdraw($wallet, $total, 'purchase', null);

                // 2. إنشاء سجلات الشراء وتحديث المخزون
                foreach ($cartItems as $item) {
                    // إنشاء سجل الشراء
                    $purchase = $user->purchases()->create([
                        'book_id' => $item->book_id,
                        'amount_paid' => $item->quantity * $item->book->price
                    ]);

                    // تحديث مرجع المعاملة بمعرف الشراء الأول (اختياري)
                    if ($transaction->reference_id === null) {
                        $transaction->update(['reference_id' => $purchase->id]);
                    }

                    // تقليل المخزون
                    $item->book->decrement('available_stock_copies', $item->quantity);
                }

                // 3. تفريغ السلة
                $this->cartService->clearCart($user);
            });

            return response()->json([
                'message' => 'تمت عملية الشراء بنجاح',
                'amount_paid' => $total,
                'remaining_balance' => $wallet->fresh()->balance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
