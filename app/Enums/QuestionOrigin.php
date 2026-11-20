<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestionOrigin: string
{
    case Sample = 'sample';
    case Manual = 'manual';
    case Imported = 'imported';

    public function label(): string
    {
        return match ($this) {
            self::Sample => 'Built-in sample',
            self::Manual => 'Manually entered',
            self::Imported => 'Excel import',
        };
    }
}
