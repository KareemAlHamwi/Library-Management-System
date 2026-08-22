<?php

namespace App\Services\User;

use App\Models\User;
use App\Repositories\Contracts\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class UserService
{
    public function __construct(private UserRepositoryInterface $userRepository) {}

    public function getAllUsers(int $perPage = 15)
    {
        return $this->userRepository->paginate($perPage);
    }
    public function getAllUsersList()
    {
        return $this->userRepository->all();
    }
    public function findById(int $id): User
    {
        return $this->userRepository->findById($id);
    }

    public function createUser(array $data): ?User
    {
        $data['password'] = Hash::make($data['password']);

        return $this->userRepository->create($data);
    }

    public function issueToken(User $user): string
    {
        $user->tokens()->delete();

        return $user->createToken('auth_token')->plainTextToken;
    }

    public function updateUser(User $user, array $data): User
    {
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $this->initiateEmailChange($user, $data['email']);
            unset($data['email']);
        }

        return $this->userRepository->update($user, collect($data)->only([
            'first_name',
            'last_name',
            'phone_number',
            'address',
            'birthdate',
            'bio',
        ])->toArray());
    }

    public function updateAvatar(User $user, $file): User
    {
        // Delete old avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $file->store('avatars', 'public');

        return $this->userRepository->update($user, ['avatar' => $path]);
    }

    private function initiateEmailChange(User $user, string $newEmail): void
    {
        $user->pending_email = $newEmail;
        $user->email_verified_at = null;
        $user->save();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($newEmail)]
        );

        Mail::raw("Verify your new email: {$url}", function ($message) use ($newEmail) {
            $message->to($newEmail)->subject('Verify your new email address');
        });
    }

    public function cancelEmailChange(User $user): void
    {
        $this->userRepository->update($user, [
            'pending_email' => null,
            'email_verified_at' => now(),
        ]);
    }

    public function verifyEmail(User $user): void
    {
        $this->userRepository->verifyEmail($user, $user->pending_email);
    }

    public function deleteUser(User $user): bool
    {

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->tokens()->delete();
        return $this->userRepository->delete($user);
    }
}
