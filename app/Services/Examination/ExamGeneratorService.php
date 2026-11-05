<?php

declare(strict_types=1);

namespace App\Services\Examination;

use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamBlueprint;
use App\Models\ExamCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamGeneratorService
{
    public function __construct(
        private readonly QuestionSelectionService $selectionService,
        private readonly ChoiceRandomizerService $randomizerService
    ) {}

    /**
     * Generate a Full Practice Examination based on a Blueprint (default 150 items).
     */
    public function generateFullExam(User $user, ExamBlueprint $blueprint): ExamAttempt
    {
        $selectedQuestions = $this->selectionService->selectQuestionsForBlueprint($blueprint);

        return DB::transaction(function () use ($user, $blueprint, $selectedQuestions) {
            $startedAt = Carbon::now();
            $durationMinutes = $blueprint->duration_minutes;
            $deadlineAt = $durationMinutes > 0 ? (clone $startedAt)->addMinutes($durationMinutes) : null;

            $attempt = ExamAttempt::create([
                'user_id' => $user->id,
                'exam_blueprint_id' => $blueprint->id,
                'category_id' => null,
                'title' => $blueprint->name,
                'mode' => ExamMode::Full->value,
                'total_questions' => $selectedQuestions->count(),
                'correct_count' => 0,
                'incorrect_count' => 0,
                'unanswered_count' => $selectedQuestions->count(),
                'score_percentage' => 0.00,
                'status' => ExamStatus::InProgress->value,
                'duration_minutes' => $durationMinutes,
                'started_at' => $startedAt,
                'deadline_at' => $deadlineAt,
                'submitted_at' => null,
            ]);

            $this->createAttemptQuestions($attempt, $selectedQuestions);

            return $attempt->load(['attemptQuestions.category', 'blueprint']);
        });
    }

    /**
     * Generate a Category-Based Practice Session.
     */
    public function generateCategoryPractice(User $user, ExamCategory $category, int $questionCount, int $durationMinutes = 0): ExamAttempt
    {
        $selectedQuestions = $this->selectionService->selectQuestionsForCategory($category, $questionCount);

        return DB::transaction(function () use ($user, $category, $selectedQuestions, $durationMinutes) {
            $startedAt = Carbon::now();
            // If duration not specified, default to ~1.13 minutes per question (matching 170 min / 150 items)
            if ($durationMinutes <= 0) {
                $durationMinutes = (int) max(10, ceil($selectedQuestions->count() * 1.2));
            }
            $deadlineAt = (clone $startedAt)->addMinutes($durationMinutes);

            $attempt = ExamAttempt::create([
                'user_id' => $user->id,
                'exam_blueprint_id' => null,
                'category_id' => $category->id,
                'title' => "{$category->name} Practice ({$selectedQuestions->count()} Items)",
                'mode' => ExamMode::Category->value,
                'total_questions' => $selectedQuestions->count(),
                'correct_count' => 0,
                'incorrect_count' => 0,
                'unanswered_count' => $selectedQuestions->count(),
                'score_percentage' => 0.00,
                'status' => ExamStatus::InProgress->value,
                'duration_minutes' => $durationMinutes,
                'started_at' => $startedAt,
                'deadline_at' => $deadlineAt,
                'submitted_at' => null,
            ]);

            $this->createAttemptQuestions($attempt, $selectedQuestions);

            return $attempt->load(['attemptQuestions.category', 'category']);
        });
    }

    /**
     * @param ExamAttempt $attempt
     * @param Collection<int, \App\Models\Question> $questions
     */
    private function createAttemptQuestions(ExamAttempt $attempt, Collection $questions): void
    {
        $questionNumber = 1;
        foreach ($questions as $question) {
            $randomized = $this->randomizerService->randomizeChoices($question);

            ExamAttemptQuestion::create([
                'exam_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'category_id' => $question->category_id,
                'question_number' => $questionNumber++,
                'question_text_snapshot' => $question->question_text,
                'category_name_snapshot' => $question->category->name,
                'explanation_snapshot' => $question->explanation,
                'correct_choice_label_snapshot' => $randomized['correct_choice_label'],
                'shuffled_choices_json' => $randomized['shuffled_choices'],
            ]);
        }
    }
}
