<?php

namespace App\Http\Controllers\Reward;

use App\Http\Controllers\Controller;
use App\Services\Reward\RewardTransactionService;
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

    /**
     * عرض رصيد النقاط
     */
    public function balance(): JsonResponse
    {
        $user = Auth::user();
        $balance = $this->rewardService->getPointsBalance($user);

        return response()->json([
            'points_balance' => $balance
        ]);
    }

    /**
     * عرض تاريخ النقاط
     */
    public function history(): JsonResponse
    {
        $user = Auth::user();
        $history = $this->rewardService->getTransactionHistory($user);

        return response()->json([
            'history' => $history
        ]);
    }

    /**
     * صرف النقاط
     */
    public function redeem(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $points = $request->input('points', 0);

            if ($points <= 0) {
                return response()->json([
                    'message' => 'يجب تحديد عدد النقاط'
                ], 400);
            }

            $this->rewardService->redeemPoints($user, $points);

            return response()->json([
                'message' => 'تم صرف النقاط بنجاح',
                'remaining_points' => $this->rewardService->getPointsBalance($user)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
