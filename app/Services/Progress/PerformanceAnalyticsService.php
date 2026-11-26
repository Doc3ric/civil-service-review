<?php

declare(strict_types=1);

namespace App\Services\Progress;

use App\Enums\CategoryCode;
use App\Enums\ExamStatus;
use App\Models\ExamAttempt;
use App\Models\ExamCategory;
use App\Models\MobilePracticeAttempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PerformanceAnalyticsService
{
    /**
     * Get comprehensive performance analytics for a user.
     *
     * @param User $user
     * @return array{
     *     total_questions_in_bank: int,
     *     total_exams_completed: int,
     *     average_score: float,
     *     total_study_days: int,
     *     current_streak: int,
     *     longest_streak: int,
     *     passing_rate: float,
     *     strongest_category: ?string,
     *     weakest_category: ?string,
     *     category_stats: array<string, array{
     *         name: string,
     *         code: string,
     *         bank_count: int,
     *         attempted_count: int,
     *         correct_count: int,
     *         percentage: float,
     *         color: string
     *     }>,
     *     topic_stats: array<int, array{
     *         topic_id: ?int,
     *         category_id: ?int,
     *         category_name: string,
     *         name: string,
     *         attempted_count: int,
     *         correct_count: int,
     *         percentage: float
     *     }>,
     *     recent_attempts: Collection<int, ExamAttempt>,
     *     chart_trends: array{
     *         labels: array<int, string>,
     *         scores: array<int, float>
     *     },
     *     chart_categories: array{
     *         labels: array<int, string>,
     *         scores: array<int, ?float>,
     *         colors: array<int, string>
     *     }
     * }
     */
    public function getAnalytics(User $user, StreakCalculationService $streakService): array
    {
        $totalQuestionsInBank = Question::published()->count();

        /** @var Collection<int, ExamAttempt> $completedAttempts */
        $completedAttempts = ExamAttempt::where('user_id', $user->id)
            ->whereIn('status', [ExamStatus::Completed->value, ExamStatus::Expired->value])
            ->orderBy('submitted_at', 'asc')
            ->get();

        $mobileAttempts = MobilePracticeAttempt::query()
            ->where('user_id', $user->id)
            ->orderBy('completed_at')
            ->get();

        $totalExamsCompleted = $completedAttempts->count() + $mobileAttempts->count();
        $scoreTotal = (float) $completedAttempts->sum('score_percentage')
            + (float) $mobileAttempts->sum('score_percentage');

        $averageScore = $totalExamsCompleted > 0
            ? round($scoreTotal / $totalExamsCompleted, 2)
            : 0.00;

        $webPassedCount = $completedAttempts->filter(function (ExamAttempt $a): bool {
            return (float) $a->score_percentage >= 80.00;
        })->count();
        $mobilePassedCount = $mobileAttempts->filter(function (MobilePracticeAttempt $attempt): bool {
            return (float) $attempt->score_percentage >= (float) $attempt->passing_percentage;
        })->count();
        $passedCount = $webPassedCount + $mobilePassedCount;

        $passingRate = $totalExamsCompleted > 0
            ? round(($passedCount / $totalExamsCompleted) * 100, 2)
            : 0.00;

        $streakInfo = $streakService->calculateStreaks($user);

        // Aggregate Category Performance across all attempts
        $categories = ExamCategory::orderBy('sort_order')->get();
        $categoryStats = [];

        foreach ($categories as $cat) {
            $catCode = $cat->code instanceof \BackedEnum ? $cat->code->value : (string) $cat->code;
            $bankCount = Question::published()->where('category_id', $cat->id)->count();

            $attemptedTotal = 0;
            $correctTotal = 0;

            foreach ($completedAttempts as $attempt) {
                $breakdown = $attempt->category_scores_json ?? [];
                if (isset($breakdown[$catCode])) {
                    $attemptedTotal += (int) ($breakdown[$catCode]['total'] ?? 0);
                    $correctTotal += (int) ($breakdown[$catCode]['correct'] ?? 0);
                }
            }

            foreach ($mobileAttempts as $attempt) {
                $breakdown = $attempt->category_scores_json ?? [];
                if (isset($breakdown[$catCode])) {
                    $attemptedTotal += (int) ($breakdown[$catCode]['total'] ?? 0);
                    $correctTotal += (int) ($breakdown[$catCode]['correct'] ?? 0);
                }
            }

            $catPercentage = $attemptedTotal > 0
                ? round(($correctTotal / $attemptedTotal) * 100, 2)
                : 0.00;

            $categoryStats[$catCode] = [
                'name' => $cat->name,
                'code' => $catCode,
                'bank_count' => $bankCount,
                'attempted_count' => $attemptedTotal,
                'correct_count' => $correctTotal,
                'percentage' => $catPercentage,
                'color' => $cat->color ?? '#2563EB',
            ];
        }

        $topicStats = DB::table('exam_attempt_questions as attempt_questions')
            ->join('exam_attempts as attempts', 'attempts.id', '=', 'attempt_questions.exam_attempt_id')
            ->leftJoin('exam_answers as answers', 'answers.exam_attempt_question_id', '=', 'attempt_questions.id')
            ->leftJoin('question_topics as topics', 'topics.id', '=', 'attempt_questions.topic_id')
            ->where('attempts.user_id', $user->id)
            ->whereIn('attempts.status', [ExamStatus::Completed->value, ExamStatus::Expired->value])
            ->whereNotNull('attempt_questions.topic_name_snapshot')
            ->select([
                'attempt_questions.topic_id',
                'topics.category_id',
                'attempt_questions.topic_name_snapshot as name',
                'attempt_questions.category_name_snapshot as category_name',
            ])
            ->selectRaw('COUNT(attempt_questions.id) as attempted_count')
            ->selectRaw('SUM(CASE WHEN answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_count')
            ->groupBy([
                'attempt_questions.topic_id',
                'topics.category_id',
                'attempt_questions.topic_name_snapshot',
                'attempt_questions.category_name_snapshot',
            ])
            ->orderBy('attempt_questions.category_name_snapshot')
            ->orderBy('attempt_questions.topic_name_snapshot')
            ->get()
            ->map(function (object $row): array {
                $attemptedCount = (int) $row->attempted_count;
                $correctCount = (int) $row->correct_count;

                return [
                    'topic_id' => $row->topic_id !== null ? (int) $row->topic_id : null,
                    'category_id' => $row->category_id !== null ? (int) $row->category_id : null,
                    'category_name' => (string) $row->category_name,
                    'name' => (string) $row->name,
                    'attempted_count' => $attemptedCount,
                    'correct_count' => $correctCount,
                    'percentage' => $attemptedCount > 0 ? round(($correctCount / $attemptedCount) * 100, 2) : 0.0,
                ];
            })
            ->all();

        $topicStatsByKey = [];
        foreach ($topicStats as $topicStat) {
            $key = implode('|', [
                $topicStat['category_id'] ?? '',
                $topicStat['topic_id'] ?? '',
                $topicStat['category_name'],
                $topicStat['name'],
            ]);
            $topicStatsByKey[$key] = $topicStat;
        }

        foreach ($mobileAttempts as $attempt) {
            foreach ($attempt->topic_scores_json ?? [] as $topicBreakdown) {
                $topicName = $topicBreakdown['name'] ?? null;
                if ($topicName === null || $topicName === '') {
                    continue;
                }

                $topicStat = [
                    'topic_id' => isset($topicBreakdown['topic_id']) ? (int) $topicBreakdown['topic_id'] : null,
                    'category_id' => isset($topicBreakdown['category_id']) ? (int) $topicBreakdown['category_id'] : null,
                    'category_name' => (string) $topicBreakdown['category_name'],
                    'name' => (string) $topicName,
                    'attempted_count' => (int) $topicBreakdown['total'],
                    'correct_count' => (int) $topicBreakdown['correct'],
                ];
                $key = implode('|', [
                    $topicStat['category_id'] ?? '',
                    $topicStat['topic_id'] ?? '',
                    $topicStat['category_name'],
                    $topicStat['name'],
                ]);

                if (isset($topicStatsByKey[$key])) {
                    $topicStatsByKey[$key]['attempted_count'] += $topicStat['attempted_count'];
                    $topicStatsByKey[$key]['correct_count'] += $topicStat['correct_count'];
                } else {
                    $topicStatsByKey[$key] = $topicStat;
                }
            }
        }

        $topicStats = array_values(array_map(function (array $topicStat): array {
            $topicStat['percentage'] = $topicStat['attempted_count'] > 0
                ? round(($topicStat['correct_count'] / $topicStat['attempted_count']) * 100, 2)
                : 0.0;

            return $topicStat;
        }, $topicStatsByKey));
        usort($topicStats, function (array $left, array $right): int {
            return [$left['category_name'], $left['name']] <=> [$right['category_name'], $right['name']];
        });

        // Identify Strongest and Weakest Category (with at least 1 attempt)
        $attemptedCategories = array_filter($categoryStats, function ($c): bool {
            return $c['attempted_count'] > 0;
        });

        $strongest = null;
        $weakest = null;

        if (count($attemptedCategories) > 0) {
            uasort($attemptedCategories, function ($a, $b): int {
                return $a['percentage'] <=> $b['percentage'];
            });

            $weakest = reset($attemptedCategories)['name'];
            $strongest = end($attemptedCategories)['name'];
        }

        // Recent attempts for dashboard
        $recentAttempts = ExamAttempt::where('user_id', $user->id)
            ->whereIn('status', [ExamStatus::Completed->value, ExamStatus::Expired->value])
            ->orderBy('submitted_at', 'desc')
            ->limit(5)
            ->get();

        // Chart Data: Score trend over time
        $trendLabels = [];
        $trendScores = [];

        $trendEvents = collect();
        foreach ($completedAttempts as $attempt) {
            if ($attempt->submitted_at !== null) {
                $trendEvents->push([
                    'timestamp' => $attempt->submitted_at->timestamp,
                    'label' => $attempt->submitted_at->format('M d'),
                    'score' => (float) $attempt->score_percentage,
                ]);
            }
        }
        foreach ($mobileAttempts as $attempt) {
            $trendEvents->push([
                'timestamp' => $attempt->completed_at->timestamp,
                'label' => 'Android · '.$attempt->completed_at->format('M d'),
                'score' => (float) $attempt->score_percentage,
            ]);
        }
        foreach ($trendEvents->sortBy('timestamp')->take(-10) as $trendEvent) {
            $trendLabels[] = $trendEvent['label'];
            $trendScores[] = $trendEvent['score'];
        }

        // Chart Data: Category Radar/Bar
        $catLabels = [];
        $catScores = [];
        $catColors = [];

        foreach ($categoryStats as $c) {
            $catLabels[] = $c['name'];
            $catScores[] = $c['attempted_count'] > 0 ? $c['percentage'] : null;
            $catColors[] = $c['color'];
        }

        return [
            'total_questions_in_bank' => $totalQuestionsInBank,
            'total_exams_completed' => $totalExamsCompleted,
            'average_score' => $averageScore,
            'total_study_days' => $streakInfo['total_study_days'],
            'current_streak' => $streakInfo['current_streak'],
            'longest_streak' => $streakInfo['longest_streak'],
            'passing_rate' => $passingRate,
            'strongest_category' => $strongest,
            'weakest_category' => $weakest,
            'category_stats' => $categoryStats,
            'topic_stats' => $topicStats,
            'recent_attempts' => $recentAttempts,
            'chart_trends' => [
                'labels' => $trendLabels,
                'scores' => $trendScores,
            ],
            'chart_categories' => [
                'labels' => $catLabels,
                'scores' => $catScores,
                'colors' => $catColors,
            ],
        ];
    }
}
