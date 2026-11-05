<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Enums\CategoryCode;
use App\Enums\QuestionDifficulty;

class QuestionValidationService
{
    /**
     * @param array<string, mixed> $row
     * @return array{
     *     is_valid: bool,
     *     errors: array<int, string>,
     *     sanitized: array<string, mixed>
     * }
     */
    public function validateRow(array $row): array
    {
        $errors = [];
        $sanitized = [];

        // Category validation
        $rawCat = strtoupper(trim((string) ($row['category'] ?? '')));
        $validCategories = [
            CategoryCode::Verbal->value,
            CategoryCode::Analytical->value,
            CategoryCode::Numerical->value,
            CategoryCode::General->value,
        ];

        // Also accept human names
        $categoryMap = [
            'VERBAL' => CategoryCode::Verbal->value,
            'VERBAL ABILITY' => CategoryCode::Verbal->value,
            'ANALYTICAL' => CategoryCode::Analytical->value,
            'ANALYTICAL ABILITY' => CategoryCode::Analytical->value,
            'NUMERICAL' => CategoryCode::Numerical->value,
            'NUMERICAL ABILITY' => CategoryCode::Numerical->value,
            'GENERAL' => CategoryCode::General->value,
            'GENERAL INFORMATION' => CategoryCode::General->value,
        ];

        $matchedCategory = $categoryMap[$rawCat] ?? null;
        if ($matchedCategory === null) {
            $errors[] = "Invalid category '{$rawCat}'. Must be VERBAL, ANALYTICAL, NUMERICAL, or GENERAL.";
        } else {
            $sanitized['category_code'] = $matchedCategory;
        }

        // Question text
        $questionText = trim((string) ($row['question'] ?? ''));
        if ($questionText === '') {
            $errors[] = 'Question text is required.';
        } elseif (mb_strlen($questionText) < 5) {
            $errors[] = 'Question text must be at least 5 characters.';
        } else {
            $sanitized['question_text'] = $questionText;
        }

        // Choices
        $choices = [
            'choice_a' => trim((string) ($row['choice_a'] ?? '')),
            'choice_b' => trim((string) ($row['choice_b'] ?? '')),
            'choice_c' => trim((string) ($row['choice_c'] ?? '')),
            'choice_d' => trim((string) ($row['choice_d'] ?? '')),
        ];

        foreach ($choices as $key => $val) {
            if ($val === '') {
                $label = strtoupper(substr($key, -1));
                $errors[] = "Choice {$label} is required.";
            } else {
                $sanitized[$key] = $val;
            }
        }

        // Correct Answer
        $rawCorrect = strtoupper(trim((string) ($row['correct_answer'] ?? '')));
        if (!in_array($rawCorrect, ['A', 'B', 'C', 'D'], true)) {
            $errors[] = "Correct answer must be A, B, C, or D (got '{$rawCorrect}').";
        } else {
            $sanitized['correct_answer'] = $rawCorrect;
        }

        // Difficulty (optional)
        $rawDiff = strtolower(trim((string) ($row['difficulty'] ?? 'medium')));
        if ($rawDiff === '') {
            $rawDiff = 'medium';
        }
        if (!in_array($rawDiff, ['easy', 'medium', 'hard'], true)) {
            $rawDiff = 'medium';
        }
        $sanitized['difficulty'] = $rawDiff;

        // Explanation & Source (optional)
        $sanitized['explanation'] = trim((string) ($row['explanation'] ?? ''));
        $sanitized['source'] = trim((string) ($row['source'] ?? ''));

        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'sanitized' => $sanitized,
        ];
    }
}
