<?php

namespace App\Repositories\Contracts\User;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findById(int $id): User;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function verifyEmail(User $user, ?string $pendingEmail): void;
    public function delete(User $user): bool;
    public function all(): \Illuminate\Database\Eloquent\Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
