<?php

declare(strict_types=1);

namespace Tests\Feature\Progress;

use App\Enums\ActivityType;
use App\Models\StudyActivity;
use App\Models\User;
use App\Services\Progress\StreakCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StreakCalculationTest extends TestCase
{
    use RefreshDatabase;

    private StreakCalculationService $streakService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->streakService = app(StreakCalculationService::class);
        $this->user = User::factory()->create();
    }

    public function test_streak_calculates_consecutive_days_correctly(): void
    {
        $timezone = 'Asia/Manila';
        $today = Carbon::now($timezone);

        // Record activity for today, yesterday, and 2 days ago
        for ($i = 0; $i < 3; $i++) {
            $date = (clone $today)->subDays($i)->format('Y-m-d');
            StudyActivity::create([
                'user_id' => $this->user->id,
                'activity_date' => $date,
                'activity_type' => ActivityType::CategoryPractice->value,
                'questions_count' => 20,
                'duration_minutes' => 25,
            ]);
        }

        $result = $this->streakService->calculateStreaks($this->user);

        $this->assertSame(3, $result['current_streak']);
        $this->assertSame(3, $result['longest_streak']);
        $this->assertSame(3, $result['total_study_days']);
        $this->assertTrue($result['studied_today']);
    }

    public function test_missed_day_breaks_current_streak(): void
    {
        $timezone = 'Asia/Manila';
        $today = Carbon::now($timezone);

        // Active today
        StudyActivity::create([
            'user_id' => $this->user->id,
            'activity_date' => $today->format('Y-m-d'),
            'activity_type' => ActivityType::CategoryPractice->value,
            'questions_count' => 20,
        ]);

        // Missed yesterday!
        // Active 2 days ago and 3 days ago (historical streak of 2)
        StudyActivity::create([
            'user_id' => $this->user->id,
            'activity_date' => (clone $today)->subDays(2)->format('Y-m-d'),
            'activity_type' => ActivityType::CategoryPractice->value,
            'questions_count' => 20,
        ]);
        StudyActivity::create([
            'user_id' => $this->user->id,
            'activity_date' => (clone $today)->subDays(3)->format('Y-m-d'),
            'activity_type' => ActivityType::CategoryPractice->value,
            'questions_count' => 20,
        ]);

        $result = $this->streakService->calculateStreaks($this->user);

        // Current streak should be only 1 (today), while longest streak should be 2
        $this->assertSame(1, $result['current_streak']);
        $this->assertSame(2, $result['longest_streak']);
        $this->assertSame(3, $result['total_study_days']);
    }

    public function test_streak_handles_month_boundaries_correctly(): void
    {
        // Simulate end of October into November
        $dates = [
            '2026-10-30',
            '2026-10-31',
            '2026-11-01',
            '2026-11-02',
        ];

        foreach ($dates as $d) {
            StudyActivity::create([
                'user_id' => $this->user->id,
                'activity_date' => $d,
                'activity_type' => ActivityType::CategoryPractice->value,
                'questions_count' => 25,
            ]);
        }

        Carbon::setTestNow(Carbon::parse('2026-11-02 14:00:00', 'Asia/Manila'));

        $result = $this->streakService->calculateStreaks($this->user);

        $this->assertSame(4, $result['current_streak']);
        $this->assertSame(4, $result['longest_streak']);

        Carbon::setTestNow(); // reset
    }
}
