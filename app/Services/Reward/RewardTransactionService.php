<?php

namespace App\Services\Reward;

use App\Models\User;
use App\Models\RewardTransaction;
use App\Models\ConversionRequest;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RewardTransactionService
{
    protected WalletService $walletService;


    const POINTS_PER_BOOK = 300;
    const LOYALTY_POINTS_PER_BOOK = 100;
    const CONVERSION_RATE = 300;
    const LOYALTY_TO_SYP_RATE = 1;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }


    public function addPurchasePoints(User $user, int $bookCount, int $purchaseId = null): RewardTransaction
    {

        if ($bookCount <= 0) {
            throw new \Exception('Number of books must be more than zero' . $bookCount);
        }

        $points = $bookCount * self::POINTS_PER_BOOK;
        $now = Carbon::now();

        return DB::transaction(function () use ($user, $points, $purchaseId, $now) {
            $reward = RewardTransaction::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'earned',
                'reason' => "purchase points for" . ($points / self::POINTS_PER_BOOK) . " books",
                'reference_id' => $purchaseId,
                'created_at' => $now
            ]);

            $user->increment('purchase_points', $points);

            return $reward;
        });
    }


    public function addLoyaltyPoints(User $user, int $bookCount, int $purchaseId = null): RewardTransaction
    {

        if ($bookCount <= 0) {
            throw new \Exception('Number of books must be more than zero' . $bookCount);
        }

        $points = $bookCount * self::LOYALTY_POINTS_PER_BOOK;
        $now = Carbon::now();

        return DB::transaction(function () use ($user, $points, $purchaseId, $now) {
            $reward = RewardTransaction::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'earned',
                'reason' => "Loyalty points (SP) enabled by the admin",
                'reference_id' => $purchaseId,
                'created_at' => $now
            ]);

            $user->increment('loyalty_points', $points);

            return $reward;
        });
    }


    public function requestConversion(User $user, int $pointsToConvert): ConversionRequest
    {

        if ($user->purchase_points < $pointsToConvert) {
            throw new \Exception('purchase points are not enough');
        }


        if ($pointsToConvert % self::CONVERSION_RATE !== 0) {
            throw new \Exception('The points must be multiples of...' . self::CONVERSION_RATE);
        }


        $loyaltyPoints = ($pointsToConvert / self::CONVERSION_RATE) * self::LOYALTY_POINTS_PER_BOOK;


        $request = ConversionRequest::create([
            'user_id' => $user->id,
            'points_to_convert' => $pointsToConvert,
            'loyalty_points_expected' => $loyaltyPoints,
            'status' => 'pending',
            'requested_at' => now()
        ]);

        return $request;
    }


    public function approveConversion(ConversionRequest $conversionRequest): void
    {
        if ($conversionRequest->status !== 'pending') {
            throw new \Exception('This request has been processed.');
        }

        $user = $conversionRequest->user;
        $pointsToConvert = $conversionRequest->points_to_convert;

        DB::transaction(function () use ($user, $pointsToConvert, $conversionRequest) {

            $user->decrement('purchase_points', $pointsToConvert);


            $loyaltyPoints = $conversionRequest->loyalty_points_expected;
            $user->increment('loyalty_points', $loyaltyPoints);


            RewardTransaction::create([
                'user_id' => $user->id,
                'points' => $pointsToConvert,
                'type' => 'spent',
                'reason' => 'Converting purchase points to loyalty points (Approved)',
                'reference_id' => $conversionRequest->id,
                'created_at' => now()
            ]);


            RewardTransaction::create([
                'user_id' => $user->id,
                'points' => $loyaltyPoints,
                'type' => 'earned',
                'reason' => 'Loyalty Points (SP) from converting purchase points',
                'reference_id' => $conversionRequest->id,
                'created_at' => now()
            ]);


            $conversionRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id()
            ]);
        });
    }


    public function rejectConversion(ConversionRequest $conversionRequest, string $reason = null): void
    {
        if ($conversionRequest->status !== 'pending') {
            throw new \Exception('This request has already been processed.');
        }

        $conversionRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_reason' => $reason
        ]);
    }


    public function purchaseWithLoyaltyPoints(User $user, float $amount, int $purchaseId = null): void
    {
        if ($amount <= 0) {
            throw new \Exception('The amount must be greater than zero.');
        }

        $requiredPoints = $amount / self::LOYALTY_TO_SYP_RATE;

        if ($user->loyalty_points < $requiredPoints) {
            throw new \Exception('Loyalty points are insufficient.');
        }

        $now = Carbon::now();

        DB::transaction(function () use ($user, $requiredPoints, $purchaseId, $now) {
            $user->decrement('loyalty_points', $requiredPoints);

            RewardTransaction::create([
                'user_id' => $user->id,
                'points' => $requiredPoints,
                'type' => 'spent',
                'reason' => 'Purchase using loyalty points (SP)',
                'reference_id' => $purchaseId,
                'created_at' => $now
            ]);
        });
    }


    public function getPurchasePointsBalance(User $user): int
    {
        return $user->purchase_points;
    }


    public function getLoyaltyPointsBalance(User $user): int
    {
        return $user->loyalty_points;
    }


    public function getTransactionHistory(User $user, int $limit = 50)
    {
        return RewardTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getUserConversionRequests(User $user)
    {
        return ConversionRequest::where('user_id', $user->id)
            ->orderBy('requested_at', 'desc')
            ->get();
    }


    public function getAllConversionRequests()
    {
        return ConversionRequest::with('user')
            ->orderBy('requested_at', 'desc')
            ->get();
    }
}
