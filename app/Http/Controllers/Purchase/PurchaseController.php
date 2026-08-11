<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\Cart\CartService;
use App\Services\Wallet\WalletService;
use App\Services\Reward\RewardTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    protected CartService $cartService;
    protected WalletService $walletService;
    protected RewardTransactionService $rewardService;

    public function __construct(
        CartService $cartService,
        WalletService $walletService,
        RewardTransactionService $rewardService
    ) {
        $this->cartService = $cartService;
        $this->walletService = $walletService;
        $this->rewardService = $rewardService;
    }


    public function checkout(): JsonResponse
    {
        try {
            $user = Auth::user();


            $cartItems = $this->cartService->getCartItems($user);


            if ($cartItems->isEmpty()) {
                return response()->json([
                    'message' => 'The cart is empty; add books first.'
                ], 400);
            }


            $bookCount = $cartItems->sum('quantity');

            if ($bookCount <= 0) {
                return response()->json([
                    'message' => 'The number of books is incorrect.',
                    'bookCount' => $bookCount
                ], 400);
            }


            $total = $this->cartService->getCartTotal($user);

            if ($total <= 0) {
                return response()->json([
                    'message' => 'Cannot purchase for a zero amount.',
                    'total' => $total
                ], 400);
            }


            $wallet = $this->walletService->getOrCreateWallet($user);


            if (!$this->walletService->hasSufficientBalance($wallet, $total)) {
                return response()->json([
                    'message' => 'Insufficient balance',
                    'balance' => $wallet->balance,
                    'required' => $total
                ], 400);
            }


            $result = DB::transaction(function () use ($user, $cartItems, $total, $wallet, $bookCount) {

                $transaction = $this->walletService->withdraw($wallet, $total, 'purchase', null);


                $purchaseIds = [];
                $now = Carbon::now();

                foreach ($cartItems as $item) {
                    $purchase = Purchase::create([
                        'user_id' => $user->id,
                        'book_id' => $item->book_id,
                        'amount_paid' => $item->quantity * $item->book->price,
                        'created_at' => $now
                    ]);

                    $purchaseIds[] = $purchase->id;


                    $item->book->decrement('available_stock_copies', $item->quantity);
                }

                $transaction->update(['reference_id' => $purchaseIds[0] ?? null]);


                $this->rewardService->addPurchasePoints($user, $bookCount, $purchaseIds[0] ?? null);


                $this->cartService->clearCart($user);

                return [
                    'transaction' => $transaction,
                    'purchase_count' => count($purchaseIds),
                    'book_count' => $bookCount
                ];
            });


            return response()->json([
                'message' => 'The purchase using the balance was successful.',
                'amount_paid' => $total,
                'remaining_balance' => $wallet->fresh()->balance,
                'transaction_id' => $result['transaction']->id,
                'items_purchased' => $result['purchase_count'],
                'books_count' => $result['book_count'],
                'points_earned' => [
                    'purchase_points' => $result['book_count'] * RewardTransactionService::POINTS_PER_BOOK,
                ],
                'current_points' => [
                    'purchase_points' => $user->fresh()->purchase_points,
                    'loyalty_points' => $user->fresh()->loyalty_points
                ],
                'note' => 'You can convert purchase points into loyalty points by submitting a conversion request.'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }


    public function checkoutWithLoyaltyPoints(): JsonResponse
    {
        try {
            $user = Auth::user();


            $cartItems = $this->cartService->getCartItems($user);


            if ($cartItems->isEmpty()) {
                return response()->json([
                    'message' => 'The cart is empty; add books first.'
                ], 400);
            }


            $bookCount = $cartItems->sum('quantity');


            if ($bookCount <= 0) {
                return response()->json([
                    'message' => 'The number of books is incorrect.',
                    'bookCount' => $bookCount
                ], 400);
            }


            $total = $this->cartService->getCartTotal($user);

            if ($total <= 0) {
                return response()->json([
                    'message' => 'Cannot purchase for a zero amount.',
                    'total' => $total
                ], 400);
            }


            $requiredPoints = $total / RewardTransactionService::LOYALTY_TO_SYP_RATE;


            if ($user->loyalty_points < $requiredPoints) {
                return response()->json([
                    'message' => 'Loyalty points are insufficient.',
                    'available_points' => $user->loyalty_points,
                    'required_points' => $requiredPoints,
                    'syp_value' => $total
                ], 400);
            }


            $result = DB::transaction(function () use ($user, $cartItems, $total, $bookCount) {


                $purchaseIds = [];
                $now = Carbon::now();

                foreach ($cartItems as $item) {
                    $purchase = Purchase::create([
                        'user_id' => $user->id,
                        'book_id' => $item->book_id,
                        'amount_paid' => $item->quantity * $item->book->price,
                        'created_at' => $now
                    ]);

                    $purchaseIds[] = $purchase->id;
                    $item->book->decrement('available_stock_copies', $item->quantity);
                }


                $this->rewardService->purchaseWithLoyaltyPoints($user, $total, $purchaseIds[0] ?? null);


                $this->rewardService->addPurchasePoints($user, $bookCount, $purchaseIds[0] ?? null);


                $this->cartService->clearCart($user);

                return [
                    'purchase_count' => count($purchaseIds),
                    'book_count' => $bookCount
                ];
            });


            return response()->json([
                'message' => 'The purchase using loyalty points was successful ',
                'amount_paid' => $total,
                'points_used' => $total / RewardTransactionService::LOYALTY_TO_SYP_RATE,
                'remaining_loyalty_points' => $user->fresh()->loyalty_points,
                'items_purchased' => $result['purchase_count'],
                'books_count' => $result['book_count'],
                'points_earned' => [
                    'purchase_points' => $result['book_count'] * RewardTransactionService::POINTS_PER_BOOK,
                ],
                'current_points' => [
                    'purchase_points' => $user->fresh()->purchase_points,
                    'loyalty_points' => $user->fresh()->loyalty_points
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
