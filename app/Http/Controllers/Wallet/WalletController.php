<?php

namespace App\Http\Controllers\Wallet;

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletService;
use App\Http\Requests\Wallet\TopUpRequest;
use App\Models\User;
use App\Models\TopUpRequest as TopUpRequestModel;
use App\Notifications\TopUpRejectedNotification;
use App\Notifications\TopUpWalletNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class WalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }


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


    public function balance(): JsonResponse
    {
        $user = Auth::user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        return response()->json([
            'balance' => $wallet->balance,
            'currency' => $wallet->currency
        ]);
    }


    public function transactions(): JsonResponse
    {
        $user = Auth::user();
        $wallet = $this->walletService->getOrCreateWallet($user);
        $transactions = $this->walletService->getTransactions($wallet);

        return response()->json([
            'transactions' => $transactions
        ]);
    }


    public function transactionsByType(string $type): JsonResponse
    {
        $user = Auth::user();
        $wallet = $this->walletService->getOrCreateWallet($user);
        $transactions = $this->walletService->getTransactionsByType($wallet, $type);

        return response()->json([
            'transactions' => $transactions
        ]);
    }


    public function deposit(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $wallet = $this->walletService->getOrCreateWallet($user);

            $request->validate([
                'amount' => 'required|numeric|min:0.01',
            ]);

            $transaction = $this->walletService->deposit(
                $wallet,
                $request->amount,
                'manual_deposit',
                null
            );

            return response()->json([
                'message' => 'The wallet has been successfully funded.',
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


    public function requestTopUp(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'nullable|string'
            ]);

            $amount = $request->amount;
            $paymentMethod = $request->payment_method ?? 'bank_transfer';

            if ($this->walletService->hasPendingTopUpRequest($user)) {
                $pendingRequest = $this->walletService->getPendingTopUpRequest($user);
                return response()->json([
                    'message' => 'You currently have a top-up request being processed. Please wait until it is approved or rejected.',
                    'pending_request' => $pendingRequest,
                    'status' => 'pending_exists'
                ], 409); // 409 Conflict
            }

            $topUpRequest = $this->walletService->requestTopUp($user, $amount, $paymentMethod);

            return response()->json([
                'message' => 'The wallet top-up request has been successfully submitted; awaiting admin approval.',
                'request' => $topUpRequest,
                'status' => 'pending'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function myTopUpRequests(): JsonResponse
    {
        $user = Auth::user();
        $requests = $this->walletService->getUserTopUpRequests($user);

        return response()->json([
            'requests' => $requests,
            'has_pending' => $this->walletService->hasPendingTopUpRequest($user),
        ]);
    }


    public function adminTopUp(TopUpRequest $request): JsonResponse
    {
        try {
            $user = User::findOrFail($request->user_id);
            $amount = $request->amount;
            $note = $request->note ?? 'Filled in by the admin';

            $wallet = $this->walletService->getOrCreateWallet($user);

            $transaction = $this->walletService->deposit(
                $wallet,
                $amount,
                'admin_topup',
                Auth::id()
            );
            Notification::send($user, new TopUpWalletNotification($transaction));
            return response()->json([
                'message' => 'The wallet has been successfully topped up by the admin.',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->first_name . ' ' . $user->last_name
                ],
                'amount' => $amount,
                'new_balance' => $wallet->fresh()->balance,
                'currency' => $wallet->currency,
                'transaction_id' => $transaction->id,
                'admin' => Auth::user()->email,
                'note' => $note
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function adminTopUpUser(Request $request, int $userId): JsonResponse
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0.01'
            ]);

            $user = User::findOrFail($userId);
            $amount = $request->amount;

            $wallet = $this->walletService->getOrCreateWallet($user);

            $transaction = $this->walletService->deposit(
                $wallet,
                $amount,
                'admin_topup',
                Auth::id()
            );
            Notification::send($user, new TopUpWalletNotification($transaction));
            return response()->json([
                'message' => 'The wallet has been successfully topped up by the admin.',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->first_name . ' ' . $user->last_name
                ],
                'amount' => $amount,
                'new_balance' => $wallet->fresh()->balance,
                'currency' => $wallet->currency,
                'transaction_id' => $transaction->id,
                'admin' => Auth::user()->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function adminApproveTopUp(int $requestId): JsonResponse
    {
        try {


            $topUpRequest = TopUpRequestModel::findOrFail($requestId);

            if ($topUpRequest->status !== 'pending') {
                return response()->json([
                    'message' => 'This request has already been processed.',
                    'current_status' => $topUpRequest->status
                ], 400);
            }

            $transaction = $this->walletService->approveTopUp($topUpRequest);
            $user = $topUpRequest->user;
            Notification::send($user, new TopUpWalletNotification($transaction));
            return response()->json([
                'message' => 'The wallet top-up request has been successfully approved.',
                'request' => $topUpRequest->fresh(),
                'transaction' => $transaction,
                'admin' => Auth::user()->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function adminRejectTopUp(Request $request, int $requestId): JsonResponse
    {
        try {
            $topUpRequest = TopUpRequestModel::findOrFail($requestId);

            if ($topUpRequest->status !== 'pending') {
                return response()->json([
                    'message' => 'This request has already been processed.',
                    'current_status' => $topUpRequest->status
                ], 400);
            }

            $reason = $request->input('reason', 'you cannot now');

            $this->walletService->rejectTopUp($topUpRequest, $reason);
            $user = $topUpRequest->user;
            Notification::send($user, new TopUpRejectedNotification($topUpRequest, $reason));
            return response()->json([
                'message' => 'The wallet top-up request was rejected.',
                'request' => $topUpRequest->fresh(),
                'reason' => $reason,
                'admin' => Auth::user()->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function adminListTopUpRequests(): JsonResponse
    {
        try {
            $requests = $this->walletService->getAllTopUpRequests();

            return response()->json([
                'requests' => $requests,
                'total' => $requests->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function withdraw(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:0.01'
            ]);

            $user = User::findOrFail($request->user_id);
            $amount = $request->amount;

            $wallet = $this->walletService->getOrCreateWallet($user);

            if (!$this->walletService->hasSufficientBalance($wallet, $amount)) {
                return response()->json([
                    'message' => 'Insufficient balance',
                    'balance' => $wallet->balance,
                    'required' => $amount
                ], 400);
            }

            $transaction = $this->walletService->withdraw(
                $wallet,
                $amount,
                'admin_withdraw',
                Auth::id()
            );

            return response()->json([
                'message' => 'The balance has been successfully withdrawn by the admin.',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email
                ],
                'amount' => $amount,
                'new_balance' => $wallet->fresh()->balance,
                'transaction_id' => $transaction->id,
                'admin' => Auth::user()->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function adminShowWallet(int $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            $wallet = $this->walletService->getOrCreateWallet($user);

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->first_name . ' ' . $user->last_name
                ],
                'wallet' => [
                    'id' => $wallet->id,
                    'balance' => $wallet->balance,
                    'currency' => $wallet->currency,
                    'created_at' => $wallet->created_at,
                    'updated_at' => $wallet->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }


    public function adminListWallets(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 50);

            $wallets = \App\Models\Wallet::with('user')
                ->orderBy('balance', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($wallet) {
                    return [
                        'user' => [
                            'id' => $wallet->user->id,
                            'email' => $wallet->user->email,
                            'name' => $wallet->user->first_name . ' ' . $wallet->user->last_name
                        ],
                        'balance' => $wallet->balance,
                        'currency' => $wallet->currency,
                        'created_at' => $wallet->created_at
                    ];
                });

            return response()->json([
                'wallets' => $wallets,
                'total' => $wallets->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
