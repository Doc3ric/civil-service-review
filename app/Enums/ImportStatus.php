<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportStatus: string
{
    case Pending = 'pending';
    case Preview = 'preview';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Preview => 'Preview Ready',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }
}
