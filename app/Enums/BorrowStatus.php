<?php

namespace App\Enums;

enum BorrowStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case RETURNED = 'returned';
    case OVERDUE = 'overdue';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::RETURNED => 'Returned',
            self::OVERDUE => 'Overdue',
            self::REJECTED => 'Rejected',
        };
    }
}
