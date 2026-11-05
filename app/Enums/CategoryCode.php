<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryCode: string
{
    case Verbal = 'VERBAL';
    case Analytical = 'ANALYTICAL';
    case Numerical = 'NUMERICAL';
    case General = 'GENERAL';

    public function label(): string
    {
        return match ($this) {
            self::Verbal => 'Verbal Ability',
            self::Analytical => 'Analytical Ability',
            self::Numerical => 'Numerical Ability',
            self::General => 'General Information',
        };
    }
}
