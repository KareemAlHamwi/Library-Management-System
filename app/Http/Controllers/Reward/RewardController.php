<?php

namespace App\Http\Controllers\Reward;

use App\Http\Controllers\Controller;
use App\Services\Reward\RewardTransactionService;
use App\Models\User;
use App\Models\ConversionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class RewardController extends Controller
{
    protected RewardTransactionService $rewardService;

    public function __construct(RewardTransactionService $rewardService)
    {
        $this->rewardService = $rewardService;
    }


    public function balance(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'purchase_points' => $this->rewardService->getPurchasePointsBalance($user),
            'loyalty_points' => $this->rewardService->getLoyaltyPointsBalance($user),
            'conversion_rate' => '300 purchase points = 100 SP'
        ]);
    }

    public function history(): JsonResponse
    {
        $user = Auth::user();
        $history = $this->rewardService->getTransactionHistory($user);

        return response()->json([
            'history' => $history
        ]);
    }


    public function requestConversion(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $points = $request->input('points', 0);

            if ($points <= 0) {
                return response()->json([
                    'message' => 'The number of points must be specified.'
                ], 400);
            }

            if ($user->purchase_points < $points) {
                return response()->json([
                    'message' => 'Insufficient purchase points',
                    'available' => $user->purchase_points,
                    'required' => $points
                ], 400);
            }

            if ($points % 300 !== 0) {
                return response()->json([
                    'message' => 'The points must be multiples of 300.',
                    'example' => '300, 600, 900, ...'
                ], 400);
            }

            $conversionRequest = $this->rewardService->requestConversion($user, $points);

            return response()->json([
                'message' => 'The point transfer request has been successfully submitted and is awaiting admin approval.',
                'request' => $conversionRequest,
                'status' => 'pending'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function myConversionRequests(): JsonResponse
    {
        $user = Auth::user();
        $requests = $this->rewardService->getUserConversionRequests($user);

        return response()->json([
            'requests' => $requests
        ]);
    }


    public function adminConvertPoints(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'points' => 'required|integer|min:300'
            ]);

            $user = User::findOrFail($request->user_id);
            $points = $request->points;

            if ($points % 300 !== 0) {
                return response()->json([
                    'message' => 'The points must be multiples of 300.'
                ], 400);
            }

            $this->rewardService->convertPurchasePointsToLoyalty($user, $points);

            return response()->json([
                'message' => 'Points successfully transferred by the admin.',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email
                ],
                'points_converted' => $points,
                'loyalty_points_earned' => ($points / 300) * 100,
                'remaining_purchase_points' => $user->fresh()->purchase_points,
                'new_loyalty_points' => $user->fresh()->loyalty_points,
                'admin' => Auth::user()->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function adminListConversionRequests(): JsonResponse
    {
        try {
            $requests = $this->rewardService->getAllConversionRequests();

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


    public function adminApproveConversion(int $requestId): JsonResponse
    {
        try {
            $conversionRequest = ConversionRequest::findOrFail($requestId);

            if ($conversionRequest->status !== 'pending') {
                return response()->json([
                    'message' => 'This request has already been processed.',
                    'current_status' => $conversionRequest->status
                ], 400);
            }

            $this->rewardService->approveConversion($conversionRequest);

            return response()->json([
                'message' => 'The transfer request has been successfully approved.',
                'request' => $conversionRequest->fresh(),
                'admin' => Auth::user()->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function adminRejectConversion(Request $request, int $requestId): JsonResponse
    {
        try {
            $conversionRequest = ConversionRequest::findOrFail($requestId);

            if ($conversionRequest->status !== 'pending') {
                return response()->json([
                    'message' => 'This request has already been processed.',
                    'current_status' => $conversionRequest->status
                ], 400);
            }

            $reason = $request->input('reason', 'The request was rejected by the admin.');

            $this->rewardService->rejectConversion($conversionRequest, $reason);

            return response()->json([
                'message' => 'The transfer request has been rejected.',
                'request' => $conversionRequest->fresh(),
                'reason' => $reason,
                'admin' => Auth::user()->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
