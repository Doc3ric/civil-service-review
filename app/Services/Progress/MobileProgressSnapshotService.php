<?php

declare(strict_types=1);

namespace App\Services\Progress;

use App\Models\StudyActivity;
use App\Models\User;
use App\Enums\ExamStatus;
use App\Models\MobilePracticeAttempt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MobileProgressSnapshotService
{
    public function __construct(
        private readonly PerformanceAnalyticsService $analyticsService,
        private readonly StreakCalculationService $streakService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $settings = $user->getOrCreateSettings();
        $analytics = $this->analyticsService->getAnalytics($user, $this->streakService);
        $streaks = $this->streakService->calculateStreaks($user);
        $studyDates = StudyActivity::query()
            ->where('user_id', $user->id)
            ->select('activity_date')
            ->distinct()
            ->orderBy('activity_date')
            ->pluck('activity_date')
            ->map(fn ($date): string => Carbon::parse($date)->format('Y-m-d'))
            ->values();

        $webMissedQuestions = DB::table('exam_attempt_questions as attempt_questions')
            ->join('exam_attempts as attempts', 'attempts.id', '=', 'attempt_questions.exam_attempt_id')
            ->join('exam_categories as categories', 'categories.id', '=', 'attempt_questions.category_id')
            ->leftJoin('exam_answers as answers', 'answers.exam_attempt_question_id', '=', 'attempt_questions.id')
            ->where('attempts.user_id', $user->id)
            ->whereIn('attempts.status', [ExamStatus::Completed->value, ExamStatus::Expired->value])
            ->where(function ($query): void {
                $query->whereNull('answers.id')->orWhere('answers.is_correct', false);
            })
            ->orderByDesc('attempts.submitted_at')
            ->limit(200)
            ->get([
                'attempt_questions.question_id',
                'categories.code as category',
                'attempt_questions.category_name_snapshot as category_name',
                'attempt_questions.topic_id',
                'attempt_questions.topic_name_snapshot as topic',
                'attempt_questions.question_text_snapshot as prompt',
                'attempt_questions.shuffled_choices_json as choices_json',
                'attempt_questions.correct_choice_label_snapshot as correct_answer',
                'answers.selected_choice_label as selected_answer',
                'attempt_questions.explanation_snapshot as explanation',
                'attempts.submitted_at as completed_at',
            ])
            ->map(function (object $row): array {
                return [
                    'question_id' => (int) $row->question_id,
                    'category' => (string) $row->category,
                    'category_name' => (string) $row->category_name,
                    'topic_id' => $row->topic_id !== null ? (int) $row->topic_id : null,
                    'topic' => $row->topic,
                    'prompt' => (string) $row->prompt,
                    'choices' => json_decode((string) $row->choices_json, true) ?: [],
                    'selected_answer' => $row->selected_answer,
                    'correct_answer' => (string) $row->correct_answer,
                    'is_correct' => false,
                    'flagged' => false,
                    'explanation' => (string) ($row->explanation ?? ''),
                    'completed_at' => $row->completed_at,
                ];
            });

        $mobileMissedQuestions = MobilePracticeAttempt::query()
            ->where('user_id', $user->id)
            ->orderByDesc('completed_at')
            ->get(['answers_json', 'completed_at'])
            ->flatMap(function (MobilePracticeAttempt $attempt): array {
                return collect($attempt->answers_json ?? [])
                    ->filter(fn (array $answer): bool => !($answer['is_correct'] ?? false))
                    ->map(function (array $answer) use ($attempt): array {
                        $answer['completed_at'] = $attempt->completed_at?->toIso8601String();

                        return $answer;
                    })
                    ->all();
            });

        $missedQuestions = $webMissedQuestions
            ->concat($mobileMissedQuestions)
            ->sortByDesc('completed_at')
            ->unique('question_id')
            ->take(200)
            ->values();

        return [
            'synced_at' => now()->toIso8601String(),
            'timezone' => $settings->timezone,
            'daily_question_target' => $streaks['daily_target'],
            'today_questions_count' => $streaks['today_questions_count'],
            'current_streak' => $streaks['current_streak'],
            'longest_streak' => $streaks['longest_streak'],
            'total_study_days' => $streaks['total_study_days'],
            'study_dates' => $studyDates,
            'total_sessions' => $analytics['total_exams_completed'],
            'average_score' => $analytics['average_score'],
            'category_stats' => array_values($analytics['category_stats']),
            'topic_stats' => $analytics['topic_stats'],
            'chart_trends' => $analytics['chart_trends'],
            'missed_questions' => $missedQuestions,
        ];
    }
}
