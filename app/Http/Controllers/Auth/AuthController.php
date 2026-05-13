<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\User\UserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->userService->createUser([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        event(new Registered($user));

        return response()->json([
            'message' => 'Registered successfully. Please verify your email.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            Auth::guard('web')->logout();

            return response()->json([
                'message' => 'Please verify your email before logging in.',
            ], 403);
        }

        $user->tokens()->delete();

        $token = $this->userService->issueToken($user);

        return response()->json([
            'message' => 'Logged in successfully.',
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
