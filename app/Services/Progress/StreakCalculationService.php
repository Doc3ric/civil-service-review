<?php

declare(strict_types=1);

namespace App\Services\Progress;

use App\Models\StudyActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StreakCalculationService
{
    /**
     * Calculate current streak, longest streak, and total unique study days.
     *
     * @param User $user
     * @return array{
     *     current_streak: int,
     *     longest_streak: int,
     *     total_study_days: int,
     *     studied_today: bool,
     *     today_questions_count: int,
     *     daily_target: int,
     *     daily_target_met: bool
     * }
     */
    public function calculateStreaks(User $user): array
    {
        $timezone = $user->settings->timezone ?? 'Asia/Manila';
        $today = Carbon::now($timezone)->startOfDay();
        $yesterday = (clone $today)->subDay();

        /** @var Collection<int, string> $dates */
        $dates = StudyActivity::where('user_id', $user->id)
            ->select('activity_date')
            ->distinct()
            ->orderBy('activity_date', 'desc')
            ->pluck('activity_date')
            ->map(function ($date): string {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->unique()
            ->values();

        $totalStudyDays = $dates->count();

        if ($totalStudyDays === 0) {
            $settings = $user->getOrCreateSettings();
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'total_study_days' => 0,
                'studied_today' => false,
                'today_questions_count' => 0,
                'daily_target' => $settings->daily_question_target,
                'daily_target_met' => false,
            ];
        }

        $todayStr = $today->format('Y-m-d');
        $yesterdayStr = $yesterday->format('Y-m-d');

        $studiedToday = $dates->contains($todayStr);
        $studiedYesterday = $dates->contains($yesterdayStr);

        // Calculate Current Streak
        $currentStreak = 0;
        if ($studiedToday || $studiedYesterday) {
            $checkDate = $studiedToday ? clone $today : clone $yesterday;

            while (true) {
                $dateStr = $checkDate->format('Y-m-d');
                if ($dates->contains($dateStr)) {
                    $currentStreak++;
                    $checkDate->subDay();
                } else {
                    break;
                }
            }
        }

        // Calculate Longest Streak across all history
        $sortedAscDates = $dates->sort()->values();
        $longestStreak = 0;
        $tempStreak = 0;
        $prevDate = null;

        foreach ($sortedAscDates as $dStr) {
            $curr = Carbon::parse($dStr)->startOfDay();

            if ($prevDate === null) {
                $tempStreak = 1;
            } else {
                $diff = (int) round($prevDate->diffInDays($curr));
                if ($diff === 1) {
                    $tempStreak++;
                } else {
                    $tempStreak = 1;
                }
            }

            if ($tempStreak > $longestStreak) {
                $longestStreak = $tempStreak;
            }

            $prevDate = $curr;
        }

        $todayQuestions = (int) StudyActivity::where('user_id', $user->id)
            ->whereDate('activity_date', $todayStr)
            ->sum('questions_count');

        $settings = $user->getOrCreateSettings();
        $target = $settings->daily_question_target;

        return [
            'current_streak' => $currentStreak,
            'longest_streak' => max($longestStreak, $currentStreak),
            'total_study_days' => $totalStudyDays,
            'studied_today' => $studiedToday,
            'today_questions_count' => $todayQuestions,
            'daily_target' => $target,
            'daily_target_met' => $todayQuestions >= $target,
        ];
    }

    /**
     * Get monthly calendar days with study activity flags.
     *
     * @param User $user
     * @param int|null $year
     * @param int|null $month
     * @return array{
     *     year: int,
     *     month: int,
     *     month_name: string,
     *     days: array<int, array{
     *         date: string,
     *         day_number: int,
     *         day_of_week: int,
     *         is_today: bool,
     *         has_activity: bool,
     *         questions_count: int,
     *         activities_count: int,
     *         is_current_month: bool
     *     }>,
     *     month_total_questions: int,
     *     month_study_days: int
     * }
     */
    public function getMonthlyCalendar(User $user, ?int $year = null, ?int $month = null): array
    {
        $timezone = $user->settings->timezone ?? 'Asia/Manila';
        $now = Carbon::now($timezone);

        $targetYear = $year ?? (int) $now->year;
        $targetMonth = $month ?? (int) $now->month;

        $targetDate = Carbon::createFromDate($targetYear, $targetMonth, 1, $timezone);
        $startOfMonth = (clone $targetDate)->startOfMonth();
        $endOfMonth = (clone $targetDate)->endOfMonth();

        $activities = StudyActivity::where('user_id', $user->id)
            ->whereBetween('activity_date', [
                $startOfMonth->format('Y-m-d'),
                $endOfMonth->format('Y-m-d'),
            ])
            ->get()
            ->groupBy(function (StudyActivity $item): string {
                return Carbon::parse($item->activity_date)->format('Y-m-d');
            });

        $todayStr = $now->format('Y-m-d');
        $days = [];
        $monthTotalQuestions = 0;
        $monthStudyDays = 0;

        $currentDay = clone $startOfMonth;
        while ($currentDay->lte($endOfMonth)) {
            $dateStr = $currentDay->format('Y-m-d');
            $dayActivities = $activities->get($dateStr, collect());
            $hasAct = $dayActivities->isNotEmpty();
            $qCount = (int) $dayActivities->sum('questions_count');

            if ($hasAct) {
                $monthStudyDays++;
                $monthTotalQuestions += $qCount;
            }

            $days[] = [
                'date' => $dateStr,
                'day_number' => (int) $currentDay->day,
                'day_of_week' => (int) $currentDay->dayOfWeek, // 0 = Sunday
                'is_today' => ($dateStr === $todayStr),
                'has_activity' => $hasAct,
                'questions_count' => $qCount,
                'activities_count' => $dayActivities->count(),
                'is_current_month' => true,
            ];

            $currentDay->addDay();
        }

        return [
            'year' => $targetYear,
            'month' => $targetMonth,
            'month_name' => $targetDate->format('F Y'),
            'days' => $days,
            'month_total_questions' => $monthTotalQuestions,
            'month_study_days' => $monthStudyDays,
        ];
    }
}
