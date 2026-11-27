<?php

declare(strict_types=1);

namespace App\Services\QuestionBank;

class QuestionTextFormatter
{
    public static function toHtml(string $questionText): string
    {
        $escapedText = e($questionText);

        return preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $escapedText) ?? $escapedText;
    }

    public static function toPlainText(string $questionText): string
    {
        return preg_replace('/\*\*(.+?)\*\*/su', '$1', $questionText) ?? $questionText;
    }
}
