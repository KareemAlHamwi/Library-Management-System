<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\User\UserService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = $this->userService->findById($id);

        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired verification link.'], 403);
        }

        $expectedEmail = $user->pending_email ?? $user->email;
        if (! hash_equals(sha1($expectedEmail), $hash)) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        if ($user->hasVerifiedEmail() && ! $user->pending_email) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $this->userService->verifyEmail($user);
        event(new Verified($user));

        return response()->json(['message' => 'Email verified successfully.']);
    }
}
