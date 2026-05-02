<?php

namespace App\Enums;

enum FileType: string
{
    case PDF = 'pdf';
    case AUDIO = 'audio';

    public function label(): string
    {
        return match ($this) {
            self::PDF => 'Pdf',
            self::AUDIO => 'Audio',
        };
    }
}
