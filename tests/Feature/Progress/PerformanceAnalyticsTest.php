<?php

declare(strict_types=1);

namespace Tests\Feature\Progress;

use App\Enums\CategoryCode;
use App\Models\ExamCategory;
use App\Models\User;
use App\Services\Examination\ExamGeneratorService;
use App\Services\Examination\ExamSubmissionService;
use App\Services\Progress\PerformanceAnalyticsService;
use App\Services\Progress\StreakCalculationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private PerformanceAnalyticsService $analyticsService;
    private StreakCalculationService $streakService;
    private ExamGeneratorService $generatorService;
    private ExamSubmissionService $submissionService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->analyticsService = app(PerformanceAnalyticsService::class);
        $this->streakService = app(StreakCalculationService::class);
        $this->generatorService = app(ExamGeneratorService::class);
        $this->submissionService = app(ExamSubmissionService::class);
        $this->user = User::firstOrFail();
    }

    public function test_performance_analytics_aggregates_multiple_attempts(): void
    {
        $verbalCat = ExamCategory::where('code', CategoryCode::Verbal->value)->firstOrFail();
        $numericalCat = ExamCategory::where('code', CategoryCode::Numerical->value)->firstOrFail();

        // Complete Verbal attempt: 10 items, all 10 correct (100%)
        $attempt1 = $this->generatorService->generateCategoryPractice($this->user, $verbalCat, 10);
        foreach ($attempt1->attemptQuestions as $aq) {
            $this->submissionService->saveAnswer($attempt1, $aq->question_number, $aq->correct_choice_label_snapshot);
        }
        $this->submissionService->submitExam($attempt1);

        // Complete Numerical attempt: 10 items, 5 correct (50%)
        $attempt2 = $this->generatorService->generateCategoryPractice($this->user, $numericalCat, 10);
        $numQuestions = $attempt2->attemptQuestions()->orderBy('question_number')->get();
        for ($i = 0; $i < 5; $i++) {
            $this->submissionService->saveAnswer($attempt2, $numQuestions[$i]->question_number, $numQuestions[$i]->correct_choice_label_snapshot);
        }
        $this->submissionService->submitExam($attempt2);

        $analytics = $this->analyticsService->getAnalytics($this->user, $this->streakService);

        $this->assertSame(2, $analytics['total_exams_completed']);
        $this->assertSame(75.0, (float) $analytics['average_score']); // (100 + 50) / 2 = 75%
        $this->assertSame(50.0, (float) $analytics['passing_rate']); // 1 of 2 passed (>= 80%)

        // Check strongest & weakest category
        $this->assertSame('Verbal Ability', $analytics['strongest_category']);
        $this->assertSame('Numerical Ability', $analytics['weakest_category']);

        // Check category stats
        $this->assertSame(100.0, $analytics['category_stats'][CategoryCode::Verbal->value]['percentage']);
        $this->assertSame(50.0, $analytics['category_stats'][CategoryCode::Numerical->value]['percentage']);
    }
}
