<?php

declare(strict_types=1);

namespace App\Services\Examination;

use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;

class ExamReviewService
{
    /**
     * @param ExamAttempt $attempt
     * @return array<int, array{
     *     question_number: int,
     *     category_name: string,
     *     question_text: string,
     *     choices: array<int, array{label: string, text: string, is_correct: bool}>,
     *     selected_choice: ?string,
     *     correct_choice: string,
     *     is_correct: bool,
     *     is_unanswered: bool,
     *     explanation: ?string,
     *     status_badge: string
     * }>
     */
    public function getReviewItems(ExamAttempt $attempt): array
    {
        $attempt->loadMissing(['attemptQuestions.answer', 'attemptQuestions.category']);

        $items = [];

        foreach ($attempt->attemptQuestions as $aq) {
            $answer = $aq->answer;
            $selected = $answer?->selected_choice_label;
            $correct = $aq->correct_choice_label_snapshot;

            $isUnanswered = ($selected === null || trim($selected) === '');
            $isCorrect = (!$isUnanswered && $selected === $correct);

            $statusBadge = $isUnanswered ? 'unanswered' : ($isCorrect ? 'correct' : 'incorrect');

            $items[] = [
                'question_number' => $aq->question_number,
                'category_name' => $aq->category_name_snapshot,
                'question_text' => $aq->question_text_snapshot,
                'choices' => $aq->shuffled_choices_json ?? [],
                'selected_choice' => $selected,
                'correct_choice' => $correct,
                'is_correct' => $isCorrect,
                'is_unanswered' => $isUnanswered,
                'explanation' => $aq->explanation_snapshot,
                'status_badge' => $statusBadge,
            ];
        }

        return $items;
    }
}
