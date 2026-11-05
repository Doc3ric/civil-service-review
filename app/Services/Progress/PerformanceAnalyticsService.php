<?php

declare(strict_types=1);

namespace App\Services\Progress;

use App\Enums\CategoryCode;
use App\Enums\ExamStatus;
use App\Models\ExamAttempt;
use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Collection;

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
     *     recent_attempts: Collection<int, ExamAttempt>,
     *     chart_trends: array{
     *         labels: array<int, string>,
     *         scores: array<int, float>
     *     },
     *     chart_categories: array{
     *         labels: array<int, string>,
     *         scores: array<int, float>,
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

        $totalExamsCompleted = $completedAttempts->count();

        $averageScore = $totalExamsCompleted > 0
            ? round((float) $completedAttempts->avg('score_percentage'), 2)
            : 0.00;

        $passedCount = $completedAttempts->filter(function (ExamAttempt $a): bool {
            return (float) $a->score_percentage >= 80.00;
        })->count();

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

        foreach ($completedAttempts->take(-10) as $attempt) {
            $trendLabels[] = $attempt->submitted_at ? $attempt->submitted_at->format('M d') : 'Exam #' . $attempt->id;
            $trendScores[] = (float) $attempt->score_percentage;
        }

        // Chart Data: Category Radar/Bar
        $catLabels = [];
        $catScores = [];
        $catColors = [];

        foreach ($categoryStats as $c) {
            $catLabels[] = $c['name'];
            $catScores[] = $c['percentage'];
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
