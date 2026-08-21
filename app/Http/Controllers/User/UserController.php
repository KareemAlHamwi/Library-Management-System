<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AvatarRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\User\PublicProfileResource;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

    public function getCurrentUser()
    {
        return response()->json(new UserResource(auth()->user()));
    }
    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);
        $this->userService->deleteUser($user);
        return response()->json([
            'message' => __('user.deleted_successfully'),
        ]);
    }


    public function changeRole(Request $request, int $userId): JsonResponse
    {


        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:member,supervisor,admin'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Incorrect Data',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($userId);
        $newRole = $request->input('role');


        if ($user->id === Auth::id() && $newRole !== 'admin') {
            return response()->json([
                'message' => 'You cannot change your own role to...' . $newRole
            ], 403);
        }


        if ($user->role === 'admin' && Auth::id() !== $user->id) {
            return response()->json([
                'message' => 'You cannot change your own role to...'
            ], 403);
        }

        $oldRole = $user->role;


        $user->update(['role' => $newRole]);

        return response()->json([
            'message' => 'The user role has been successfully changed.',
            'user' => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'old_role' => $oldRole,
                'new_role' => $user->role,
            ],
            'admin' => Auth::user()->email,
        ]);
    }
}
