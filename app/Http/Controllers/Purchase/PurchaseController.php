<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Purchase;
use App\Models\User;
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

    public function getAllMypurchases()
    {
        $purchases = Auth::user()->purchases;

        return response()->json($purchases, 200);
    }

    public function getAllPurchases(): JsonResponse
    {
        try {


            $purchases = Purchase::with(['user', 'book'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($purchase) {
                    return [
                        'purchase_id' => $purchase->id,
                        'user' => [
                            'id' => $purchase->user->id,
                            'name' => $purchase->user->first_name . ' ' . $purchase->user->last_name,
                            'email' => $purchase->user->email,
                        ],
                        'book' => [
                            'id' => $purchase->book->id,
                            'title' => $purchase->book->title,
                            'price' => $purchase->book->price,
                        ],
                        'amount_paid' => $purchase->amount_paid,
                        'purchased_at' => $purchase->created_at,
                    ];
                });

            return response()->json([
                'purchases' => $purchases,
                'total' => $purchases->count(),
                'total_amount' => $purchases->sum('amount_paid'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getUserPurchases(int $userId): JsonResponse
    {
        try {


            $user = User::findOrFail($userId);

            $purchases = Purchase::with(['book'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($purchase) {
                    return [
                        'purchase_id' => $purchase->id,
                        'book' => [
                            'id' => $purchase->book->id,
                            'title' => $purchase->book->title,
                            'price' => $purchase->book->price,
                            'cover_image' => $purchase->book->cover_image,
                        ],
                        'amount_paid' => $purchase->amount_paid,
                        'purchased_at' => $purchase->created_at,
                    ];
                });

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                ],
                'purchases' => $purchases,
                'total_books' => $purchases->count(),
                'total_spent' => $purchases->sum('amount_paid'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }
    public function getBookBuyers(int $bookId): JsonResponse
    {
        try {


            $book = Book::findOrFail($bookId);

            $purchases = Purchase::with(['user'])
                ->where('book_id', $bookId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($purchase) {
                    return [
                        'purchase_id' => $purchase->id,
                        'user' => [
                            'id' => $purchase->user->id,
                            'name' => $purchase->user->first_name . ' ' . $purchase->user->last_name,
                            'email' => $purchase->user->email,
                            'phone' => $purchase->user->phone_number,
                        ],
                        'amount_paid' => $purchase->amount_paid,
                        'purchased_at' => $purchase->created_at,
                    ];
                });


            $totalSales = $purchases->sum('amount_paid');
            $totalBuyers = $purchases->unique('user_id')->count();

            return response()->json([
                'book' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'price' => $book->price,
                    'cover_image' => $book->cover_image,
                    'total_copies_sold' => $purchases->count(),
                ],
                'buyers' => $purchases,
                'total_buyers' => $totalBuyers,
                'total_revenue' => $totalSales,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function getPurchaseStatistics(): JsonResponse
    {
        try {


            $totalPurchases = Purchase::count();
            $totalRevenue = Purchase::sum('amount_paid');


            $totalBooksSold = Purchase::count();




            $topBooks = Purchase::select('book_id', DB::raw('count(*) as total_sales'))
                ->with('book')
                ->groupBy('book_id')
                ->orderBy('total_sales', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'book_id' => $item->book_id,
                        'title' => $item->book->title ?? 'Unknown',
                        'total_sales' => $item->total_sales,
                    ];
                });


            $topUsers = Purchase::select('user_id', DB::raw('count(*) as total_purchases, sum(amount_paid) as total_spent'))
                ->with('user')
                ->groupBy('user_id')
                ->orderBy('total_spent', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->user_id,
                        'name' => $item->user->first_name . ' ' . $item->user->last_name ?? 'Unknown',
                        'email' => $item->user->email ?? 'Unknown',
                        'total_purchases' => $item->total_purchases,
                        'total_spent' => $item->total_spent,
                    ];
                });

            return response()->json([
                'statistics' => [
                    'total_purchases' => $totalPurchases,
                    'total_revenue' => $totalRevenue,
                    'total_books_sold' => $totalBooksSold,
                ],
                'top_books' => $topBooks,
                'top_users' => $topUsers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // public function getPurchaseStatistics(): JsonResponse
    // {
    //     try {

    //         $totalPurchases = Purchase::count();
    //         $totalRevenue = Purchase::sum('amount_paid');
    //         $totalBooksSold = Purchase::sum('book_id');

    //         $topBooks = Purchase::select('book_id', DB::raw('count(*) as total_sales'))
    //             ->with('book')
    //             ->groupBy('book_id')
    //             ->orderBy('total_sales', 'desc')
    //             ->limit(5)
    //             ->get()
    //             ->map(function ($item) {
    //                 return [
    //                     'book_id' => $item->book_id,
    //                     'title' => $item->book->title ?? 'Unknown',
    //                     'total_sales' => $item->total_sales,
    //                 ];
    //             });

    //         $topUsers = Purchase::select('user_id', DB::raw('count(*) as total_purchases, sum(amount_paid) as total_spent'))
    //             ->with('user')
    //             ->groupBy('user_id')
    //             ->orderBy('total_spent', 'desc')
    //             ->limit(5)
    //             ->get()
    //             ->map(function ($item) {
    //                 return [
    //                     'user_id' => $item->user_id,
    //                     'name' => $item->user->first_name . ' ' . $item->user->last_name ?? 'Unknown',
    //                     'email' => $item->user->email ?? 'Unknown',
    //                     'total_purchases' => $item->total_purchases,
    //                     'total_spent' => $item->total_spent,
    //                 ];
    //             });

    //         return response()->json([
    //             'statistics' => [
    //                 'total_purchases' => $totalPurchases,
    //                 'total_revenue' => $totalRevenue,
    //                 'total_books_sold' => $totalBooksSold,
    //             ],
    //             'top_books' => $topBooks,
    //             'top_users' => $topUsers,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
