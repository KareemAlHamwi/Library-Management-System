<?php

namespace App\Enums;

enum UserRole: string {
    case ADMIN   = 'admin';
    case MEMBER  = 'member';
    case SUPERVISOR  = 'supervisor';

    public function label(): string {
        return match ($this) {
            self::ADMIN   => 'Admin',
            self::MEMBER  => 'Member',
            self::SUPERVISOR  => 'Supervisor',
        };
    }
}
