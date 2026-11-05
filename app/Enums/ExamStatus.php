<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Expired = 'expired';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Expired => 'Expired',
            self::Abandoned => 'Abandoned',
        };
    }
}
