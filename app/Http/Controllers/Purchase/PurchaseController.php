<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
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

            // 1. جلب عناصر السلة
            $cartItems = $this->cartService->getCartItems($user);

            // ✅ التحقق من أن السلة ليست فارغة
            if ($cartItems->isEmpty()) {
                return response()->json([
                    'message' => 'السلة فارغة، أضف كتباً أولاً'
                ], 400);
            }

            // 2. حساب الإجمالي
            $total = $this->cartService->getCartTotal($user);

            // ✅ التحقق من أن المبلغ أكبر من صفر
            if ($total <= 0) {
                return response()->json([
                    'message' => 'لا يمكن الشراء بمبلغ صفر',
                    'total' => $total,
                    'cart_items' => $cartItems->map(function ($item) {
                        return [
                            'book_id' => $item->book_id,
                            'title' => $item->book->title ?? 'Unknown',
                            'quantity' => $item->quantity,
                            'price' => $item->book->price ?? 0,
                            'subtotal' => ($item->quantity * ($item->book->price ?? 0))
                        ];
                    })
                ], 400);
            }

            // 3. الحصول على المحفظة
            $wallet = $this->walletService->getOrCreateWallet($user);

            // 4. التحقق من الرصيد
            if (!$this->walletService->hasSufficientBalance($wallet, $total)) {
                return response()->json([
                    'message' => 'الرصيد غير كافٍ',
                    'balance' => $wallet->balance,
                    'required' => $total,
                    'difference' => $total - $wallet->balance
                ], 400);
            }

            // 5. تنفيذ الشراء في ترانزاكشن
            $transaction = DB::transaction(function () use ($user, $cartItems, $total, $wallet) {
                // 5.1 إنشاء معاملة السحب
                $transaction = $this->walletService->withdraw($wallet, $total, 'purchase', null);

                // 5.2 إنشاء سجلات الشراء وتحديث المخزون
                $purchaseIds = [];
                foreach ($cartItems as $item) {
                    // التحقق من أن الكتاب موجود
                    if (!$item->book) {
                        throw new \Exception("الكتاب غير موجود: ID {$item->book_id}");
                    }

                    // التحقق من المخزون
                    if ($item->book->available_stock_copies < $item->quantity) {
                        throw new \Exception("الكتاب '{$item->book->title}' غير متوفر بهذه الكمية");
                    }

                    // إنشاء سجل الشراء
                    $purchase = $user->purchases()->create([
                        'book_id' => $item->book_id,
                        'amount_paid' => $item->quantity * $item->book->price
                    ]);

                    $purchaseIds[] = $purchase->id;

                    // تقليل المخزون
                    $item->book->decrement('available_stock_copies', $item->quantity);
                }

                // تحديث مرجع المعاملة بأول purchase_id
                $transaction->update(['reference_id' => $purchaseIds[0] ?? null]);

                // 5.3 تفريغ السلة
                $this->cartService->clearCart($user);

                return $transaction;
            });

            return response()->json([
                'message' => 'تمت عملية الشراء بنجاح 🎉',
                'amount_paid' => $total,
                'remaining_balance' => $wallet->fresh()->balance,
                'transaction_id' => $transaction->id,
                'items_purchased' => $cartItems->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}
