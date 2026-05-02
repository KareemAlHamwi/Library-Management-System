<?php

namespace App\Services\User;

use App\Repositories\Contracts\User\UserRepositoryInterface;

class UserService
{
    public function __construct(private UserRepositoryInterface $userRepository) {}
}
