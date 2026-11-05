<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityType: string
{
    case FullExam = 'full_exam';
    case CategoryPractice = 'category_practice';
    case QuestionReview = 'question_review';

    public function label(): string
    {
        return match ($this) {
            self::FullExam => 'Full Practice Examination',
            self::CategoryPractice => 'Category Practice Session',
            self::QuestionReview => 'Question Review',
        };
    }
}
