<?php

namespace App\Enums;

enum RewardType: string
{
    case EARNED = 'earned';
    case SPENT = 'spent';

    public function label(): string
    {
        return match ($this) {
            self::EARNED => 'Earned',
            self::SPENT => 'Spent',
        };
    }
}
