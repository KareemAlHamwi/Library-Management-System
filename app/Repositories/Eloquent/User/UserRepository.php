<?php

namespace App\Repositories\Eloquent\User;

use App\Models\User;
use App\Repositories\Contracts\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): User
    {
        return User::findOrFail($id);
    }

    public function create(array $data): User
    {
        $user = User::create($data);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user;
    }

    public function verifyEmail(User $user, ?string $pendingEmail): void
    {
        if ($pendingEmail) {
            $user->email = $pendingEmail;
            $user->pending_email = null;
        }

        $user->markEmailAsVerified();
        $user->save();
    }
    public function delete(User $user): bool
    {
        return $user->delete();
    }
    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return User::all();
    }
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::paginate($perPage);
    }
}
