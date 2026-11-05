<?php

declare(strict_types=1);

namespace App\Services\Examination;

use App\Models\ExamBlueprint;
use App\Models\ExamCategory;
use App\Models\Question;
use Illuminate\Support\Collection;
use RuntimeException;

class QuestionSelectionService
{
    /**
     * Check if all categories in a blueprint have sufficient published questions.
     *
     * @param ExamBlueprint $blueprint
     * @return array{
     *     sufficient: bool,
     *     deficits: array<string, array{required: int, available: int}>
     * }
     */
    public function checkBlueprintAvailability(ExamBlueprint $blueprint): array
    {
        $deficits = [];
        $sufficient = true;

        foreach ($blueprint->blueprintCategories as $bc) {
            $cat = $bc->category;
            $available = Question::published()
                ->where('category_id', $cat->id)
                ->count();

            if ($available < $bc->question_count) {
                $sufficient = false;
                $deficits[$cat->name] = [
                    'required' => $bc->question_count,
                    'available' => $available,
                ];
            }
        }

        return [
            'sufficient' => $sufficient,
            'deficits' => $deficits,
        ];
    }

    /**
     * Select random non-duplicate questions per category matching blueprint allocations.
     *
     * @param ExamBlueprint $blueprint
     * @return Collection<int, Question>
     */
    public function selectQuestionsForBlueprint(ExamBlueprint $blueprint): Collection
    {
        $availability = $this->checkBlueprintAvailability($blueprint);
        if (!$availability['sufficient']) {
            $details = [];
            foreach ($availability['deficits'] as $catName => $info) {
                $details[] = "{$catName} requires {$info['required']} questions, but only {$info['available']} are published";
            }
            throw new RuntimeException('Cannot generate examination. Insufficient published questions: ' . implode('; ', $details));
        }

        $allQuestions = collect();

        foreach ($blueprint->blueprintCategories as $bc) {
            /** @var Collection<int, Question> $categoryQuestions */
            $categoryQuestions = Question::published()
                ->with(['choices', 'category'])
                ->where('category_id', $bc->category_id)
                ->inRandomOrder()
                ->limit($bc->question_count)
                ->get();

            $allQuestions = $allQuestions->concat($categoryQuestions);
        }

        return $allQuestions->shuffle()->values();
    }

    /**
     * Select random questions for a single category practice.
     *
     * @param ExamCategory $category
     * @param int $requestedCount
     * @return Collection<int, Question>
     */
    public function selectQuestionsForCategory(ExamCategory $category, int $requestedCount): Collection
    {
        $available = Question::published()
            ->where('category_id', $category->id)
            ->count();

        if ($available === 0) {
            throw new RuntimeException("No published questions available for {$category->name}.");
        }

        $countToSelect = min($requestedCount, $available);

        return Question::published()
            ->with(['choices', 'category'])
            ->where('category_id', $category->id)
            ->inRandomOrder()
            ->limit($countToSelect)
            ->get();
    }
}
