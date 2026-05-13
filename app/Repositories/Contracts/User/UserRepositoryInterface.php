<?php

namespace App\Repositories\Contracts\User;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function verifyEmail(User $user, ?string $pendingEmail): void;
}
