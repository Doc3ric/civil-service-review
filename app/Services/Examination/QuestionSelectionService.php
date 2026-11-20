<?php

declare(strict_types=1);

namespace App\Services\Examination;

use App\Models\ExamBlueprint;
use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
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
     *     deficits: array<string, array{required: int, available: int, reason?: string}>
     * }
     */
    public function checkBlueprintAvailability(ExamBlueprint $blueprint): array
    {
        $deficits = [];
        $sufficient = true;

        $blueprint->loadMissing('blueprintCategories.topicAllocations.topic');

        foreach ($blueprint->blueprintCategories as $bc) {
            $cat = $bc->category;

            if ($bc->topicAllocations->isNotEmpty()) {
                $allocatedTotal = (int) $bc->topicAllocations->sum('question_count');
                if ($allocatedTotal !== $bc->question_count) {
                    $sufficient = false;
                    $deficits[$cat->name] = [
                        'required' => $bc->question_count,
                        'available' => $allocatedTotal,
                        'reason' => "Topic quotas total {$allocatedTotal}; they must total {$bc->question_count}.",
                    ];
                    continue;
                }

                foreach ($bc->topicAllocations as $allocation) {
                    $topic = $allocation->topic;
                    if ((int) $topic->category_id !== (int) $bc->category_id) {
                        $sufficient = false;
                        $deficits["{$cat->name} — {$topic->name}"] = [
                            'required' => $allocation->question_count,
                            'available' => 0,
                            'reason' => "{$topic->name} does not belong to {$cat->name}.",
                        ];
                        continue;
                    }

                    $available = Question::published()
                        ->where('category_id', $bc->category_id)
                        ->where('topic_id', $topic->id)
                        ->count();
                    if ($available < $allocation->question_count) {
                        $sufficient = false;
                        $deficits["{$cat->name} — {$topic->name}"] = [
                            'required' => $allocation->question_count,
                            'available' => $available,
                        ];
                    }
                }

                continue;
            }

            $available = Question::published()->where('category_id', $cat->id)->count();

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
                $details[] = $info['reason'] ?? "{$catName} requires {$info['required']} questions, but only {$info['available']} are published";
            }
            throw new RuntimeException('Cannot generate examination. Insufficient published questions: ' . implode('; ', $details));
        }

        $allQuestions = collect();

        foreach ($blueprint->blueprintCategories as $bc) {
            if ($bc->topicAllocations->isNotEmpty()) {
                foreach ($bc->topicAllocations as $allocation) {
                    $topicQuestions = Question::published()
                        ->with(['choices', 'category', 'topic'])
                        ->where('category_id', $bc->category_id)
                        ->where('topic_id', $allocation->topic_id)
                        ->inRandomOrder()
                        ->limit($allocation->question_count)
                        ->get();
                    $allQuestions = $allQuestions->concat($topicQuestions);
                }
            } else {
                /** @var Collection<int, Question> $categoryQuestions */
                $categoryQuestions = Question::published()
                    ->with(['choices', 'category', 'topic'])
                    ->where('category_id', $bc->category_id)
                    ->inRandomOrder()
                    ->limit($bc->question_count)
                    ->get();

                $allQuestions = $allQuestions->concat($categoryQuestions);
            }
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
            ->with(['choices', 'category', 'topic'])
            ->where('category_id', $category->id)
            ->inRandomOrder()
            ->limit($countToSelect)
            ->get();
    }

    /**
     * Select random published questions for a single topic.
     *
     * @return Collection<int, Question>
     */
    public function selectQuestionsForTopic(QuestionTopic $topic, int $requestedCount): Collection
    {
        $available = Question::published()->where('topic_id', $topic->id)->count();

        if ($available === 0) {
            throw new \RuntimeException("No published questions available for {$topic->name}.");
        }

        return Question::published()
            ->with(['choices', 'category', 'topic'])
            ->where('topic_id', $topic->id)
            ->inRandomOrder()
            ->limit(min($requestedCount, $available))
            ->get();
    }
}
