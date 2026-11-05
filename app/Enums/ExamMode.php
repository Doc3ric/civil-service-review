<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamMode: string
{
    case Full = 'full';
    case Category = 'category';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Full Practice Examination',
            self::Category => 'Category Practice',
        };
    }
}
