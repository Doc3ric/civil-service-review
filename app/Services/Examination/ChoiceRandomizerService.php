<?php

declare(strict_types=1);

namespace App\Services\Examination;

use App\Models\Question;
use App\Models\QuestionChoice;
use Illuminate\Support\Collection;

class ChoiceRandomizerService
{
    /**
     * Randomize the choices for a question while retaining correct answer mapping.
     *
     * @param Question $question
     * @return array{
     *     shuffled_choices: array<int, array{
     *         label: string,
     *         text: string,
     *         original_label: string,
     *         is_correct: bool
     *     }>,
     *     correct_choice_label: string
     * }
     */
    public function randomizeChoices(Question $question): array
    {
        /** @var Collection<int, QuestionChoice> $choices */
        $choices = $question->choices->shuffle()->values();

        $shuffled = [];
        $newCorrectLabel = 'A';
        $labels = ['A', 'B', 'C', 'D'];

        foreach ($choices as $index => $choice) {
            $currentLabel = $labels[$index] ?? chr(65 + $index);
            $isCorrect = (bool) $choice->is_correct;

            if ($isCorrect) {
                $newCorrectLabel = $currentLabel;
            }

            $shuffled[] = [
                'label' => $currentLabel,
                'text' => $choice->choice_text,
                'original_label' => $choice->choice_label,
                'is_correct' => $isCorrect,
            ];
        }

        return [
            'shuffled_choices' => $shuffled,
            'correct_choice_label' => $newCorrectLabel,
        ];
    }
}
