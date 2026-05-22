<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AvatarRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\User\PublicProfileResource;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);

        return response()->json(new PublicProfileResource($user));
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        $user = $this->userService->updateUser($request->user(), $request->validated());

        return response()->json([
            'message' => __('profile.updated'),
            'user' => new UserResource($user),
        ]);
    }

    public function updateAvatar(AvatarRequest $request): JsonResponse
    {
        $user = $this->userService->updateAvatar(
            $request->user(),
            $request->file('avatar')
        );

        return response()->json([
            'message' => __('profile.avatar_updated'),
            'avatar' => $user->avatar_url,
        ]);
    }

    public function cancelEmailChange(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->pending_email) {
            return response()->json([
                'message' => __('profile.no_pending_email'),
            ], 409);
        }

        $this->userService->cancelEmailChange($user);

        return response()->json([
            'message' => __('profile.email_change_cancelled'),
            'email' => $user->email,
        ]);
    }
}
