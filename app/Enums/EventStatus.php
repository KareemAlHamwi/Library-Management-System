<?php

namespace App\Enums;

enum EventStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case CLOSED = 'closed';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::OPEN => 'Open',
            self::CLOSED => 'Closed',
            self::ARCHIVED => 'Archived',
        };
    }
}
