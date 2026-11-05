<?php

declare(strict_types=1);

namespace App\Services\Examination;

use App\Enums\ExamStatus;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Services\Progress\StudyActivityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExamSubmissionService
{
    public function __construct(
        private readonly ExamScoringService $scoringService,
        private readonly StudyActivityService $studyActivityService
    ) {}

    /**
     * Persist or update an answer for an active attempt.
     */
    public function saveAnswer(ExamAttempt $attempt, int $questionNumber, ?string $selectedChoice): ExamAnswer
    {
        if ($attempt->isCompleted() || $attempt->fresh()?->isCompleted()) {
            throw new RuntimeException('Cannot update answers on a completed examination.');
        }

        // Check if server deadline has passed
        if ($attempt->isExpired()) {
            $this->submitExam($attempt, isExpired: true);
            throw new RuntimeException('Examination time has expired. The exam has been automatically submitted.');
        }

        /** @var ExamAttemptQuestion $attemptQuestion */
        $attemptQuestion = $attempt->attemptQuestions()
            ->where('question_number', $questionNumber)
            ->firstOrFail();

        $selectedChoice = $selectedChoice !== null ? strtoupper(trim($selectedChoice)) : null;
        $isCorrect = ($selectedChoice === $attemptQuestion->correct_choice_label_snapshot);

        return ExamAnswer::updateOrCreate(
            [
                'exam_attempt_id' => $attempt->id,
                'exam_attempt_question_id' => $attemptQuestion->id,
            ],
            [
                'selected_choice_label' => $selectedChoice,
                'is_correct' => $isCorrect,
                'answered_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Finalize and score an examination attempt.
     * Uses pessimistic locking to guarantee idempotency and prevent double submissions.
     *
     * @param ExamAttempt $attempt
     * @param bool $isExpired
     * @return array{
     *     total: int,
     *     correct: int,
     *     incorrect: int,
     *     unanswered: int,
     *     percentage: float,
     *     is_passed: bool
     * }
     */
    public function submitExam(ExamAttempt $attempt, bool $isExpired = false): array
    {
        return DB::transaction(function () use ($attempt, $isExpired) {
            /** @var ExamAttempt $lockedAttempt */
            $lockedAttempt = ExamAttempt::where('id', $attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->isCompleted()) {
                $scores = $this->scoringService->calculateScore($lockedAttempt);
                return [
                    'total' => $scores['total'],
                    'correct' => $scores['correct'],
                    'incorrect' => $scores['incorrect'],
                    'unanswered' => $scores['unanswered'],
                    'percentage' => $scores['percentage'],
                    'is_passed' => $scores['is_passed'],
                ];
            }

            $now = Carbon::now();
            $status = $isExpired ? ExamStatus::Expired : ExamStatus::Completed;

            // Compute score
            $scoreData = $this->scoringService->calculateScore($lockedAttempt);

            $durationMinutes = 0;
            if ($lockedAttempt->started_at) {
                $durationMinutes = (int) max(1, round($lockedAttempt->started_at->diffInMinutes($now)));
            }

            $lockedAttempt->update([
                'status' => $status->value,
                'correct_count' => $scoreData['correct'],
                'incorrect_count' => $scoreData['incorrect'],
                'unanswered_count' => $scoreData['unanswered'],
                'score_percentage' => $scoreData['percentage'],
                'category_scores_json' => $scoreData['category_breakdown'],
                'submitted_at' => $now,
                'duration_minutes' => $durationMinutes,
            ]);

            $attempt->refresh();

            // Record qualifying study activity
            $this->studyActivityService->recordExamActivity($lockedAttempt);

            return [
                'total' => $scoreData['total'],
                'correct' => $scoreData['correct'],
                'incorrect' => $scoreData['incorrect'],
                'unanswered' => $scoreData['unanswered'],
                'percentage' => $scoreData['percentage'],
                'is_passed' => $scoreData['is_passed'],
            ];
        });
    }
}
