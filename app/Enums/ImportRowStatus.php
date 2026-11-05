<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportRowStatus: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Duplicate = 'duplicate';
    case Imported = 'imported';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valid',
            self::Invalid => 'Invalid',
            self::Duplicate => 'Duplicate',
            self::Imported => 'Imported',
            self::Skipped => 'Skipped',
        };
    }
}
