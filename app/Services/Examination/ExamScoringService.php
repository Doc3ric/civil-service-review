<?php

declare(strict_types=1);

namespace App\Services\Examination;

use App\Models\ExamAttempt;

class ExamScoringService
{
    /**
     * Compute score details for an examination attempt.
     *
     * @param ExamAttempt $attempt
     * @return array{
     *     total: int,
     *     correct: int,
     *     incorrect: int,
     *     unanswered: int,
     *     percentage: float,
     *     is_passed: bool,
     *     category_breakdown: array<string, array{
     *         name: string,
     *         total: int,
     *         correct: int,
     *         incorrect: int,
     *         unanswered: int,
     *         percentage: float
     *     }>,
     *     strongest_category: ?string,
     *     weakest_category: ?string
     * }
     */
    public function calculateScore(ExamAttempt $attempt): array
    {
        $attempt->loadMissing(['attemptQuestions.answer', 'attemptQuestions.category', 'blueprint']);

        $total = $attempt->attemptQuestions->count();
        $correct = 0;
        $incorrect = 0;
        $unanswered = 0;

        /** @var array<string, array{name: string, total: int, correct: int, incorrect: int, unanswered: int, percentage: float}> $categories */
        $categories = [];

        foreach ($attempt->attemptQuestions as $aq) {
            $catCode = $aq->category->code instanceof \BackedEnum ? $aq->category->code->value : (string) $aq->category->code;
            $catName = $aq->category_name_snapshot;

            if (!isset($categories[$catCode])) {
                $categories[$catCode] = [
                    'name' => $catName,
                    'total' => 0,
                    'correct' => 0,
                    'incorrect' => 0,
                    'unanswered' => 0,
                    'percentage' => 0.0,
                ];
            }

            $categories[$catCode]['total']++;

            $answer = $aq->answer;
            if ($answer === null || empty($answer->selected_choice_label)) {
                $unanswered++;
                $categories[$catCode]['unanswered']++;
            } elseif ($answer->selected_choice_label === $aq->correct_choice_label_snapshot) {
                $correct++;
                $categories[$catCode]['correct']++;
            } else {
                $incorrect++;
                $categories[$catCode]['incorrect']++;
            }
        }

        foreach ($categories as $code => $data) {
            $categories[$code]['percentage'] = $data['total'] > 0
                ? round(($data['correct'] / $data['total']) * 100, 2)
                : 0.0;
        }

        $percentage = $total > 0 ? round(($correct / $total) * 100, 2) : 0.0;
        $passingTarget = (float) ($attempt->blueprint->passing_percentage ?? 80.00);
        $isPassed = $percentage >= $passingTarget;

        // Determine strongest and weakest categories
        $strongest = null;
        $weakest = null;
        $highestPct = -1.0;
        $lowestPct = 101.0;

        foreach ($categories as $code => $data) {
            if ($data['percentage'] > $highestPct) {
                $highestPct = $data['percentage'];
                $strongest = $data['name'];
            }
            if ($data['percentage'] < $lowestPct) {
                $lowestPct = $data['percentage'];
                $weakest = $data['name'];
            }
        }

        return [
            'total' => $total,
            'correct' => $correct,
            'incorrect' => $incorrect,
            'unanswered' => $unanswered,
            'percentage' => $percentage,
            'is_passed' => $isPassed,
            'category_breakdown' => $categories,
            'strongest_category' => $strongest,
            'weakest_category' => $weakest,
        ];
    }
}
