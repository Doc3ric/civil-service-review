<?php

declare(strict_types=1);

namespace Tests\Feature\Examination;

use App\Enums\ExamStatus;
use App\Models\ExamCategory;
use App\Models\User;
use App\Services\Examination\ExamGeneratorService;
use App\Services\Examination\ExamSubmissionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ExamSubmissionAndScoringTest extends TestCase
{
    use RefreshDatabase;

    private ExamGeneratorService $generatorService;
    private ExamSubmissionService $submissionService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->generatorService = app(ExamGeneratorService::class);
        $this->submissionService = app(ExamSubmissionService::class);
        $this->user = User::firstOrFail();
    }

    public function test_can_save_and_update_answers_during_active_exam(): void
    {
        $category = ExamCategory::firstOrFail();
        $attempt = $this->generatorService->generateCategoryPractice($this->user, $category, 5);

        // Save answer for question 1
        $answer = $this->submissionService->saveAnswer($attempt, 1, 'A');
        $this->assertSame('A', $answer->selected_choice_label);

        // Change answer to 'B'
        $updatedAnswer = $this->submissionService->saveAnswer($attempt, 1, 'B');
        $this->assertSame('B', $updatedAnswer->selected_choice_label);
    }

    public function test_scoring_formula_calculates_correct_percentage(): void
    {
        $category = ExamCategory::firstOrFail();
        $attempt = $this->generatorService->generateCategoryPractice($this->user, $category, 10);

        // Answer 8 questions correctly, 1 incorrectly, leave 1 unanswered
        $attemptQuestions = $attempt->attemptQuestions()->orderBy('question_number')->get();

        for ($i = 0; $i < 8; $i++) {
            $aq = $attemptQuestions[$i];
            $this->submissionService->saveAnswer($attempt, $aq->question_number, $aq->correct_choice_label_snapshot);
        }

        // 9th question answered incorrectly
        $aq9 = $attemptQuestions[8];
        $wrongChoice = ($aq9->correct_choice_label_snapshot === 'A') ? 'B' : 'A';
        $this->submissionService->saveAnswer($attempt, $aq9->question_number, $wrongChoice);

        // 10th left unanswered

        $result = $this->submissionService->submitExam($attempt);

        $this->assertSame(10, $result['total']);
        $this->assertSame(8, $result['correct']);
        $this->assertSame(1, $result['incorrect']);
        $this->assertSame(1, $result['unanswered']);
        $this->assertSame(80.00, (float) $result['percentage']);
        $this->assertTrue($result['is_passed']);

        // Assert attempt record
        $fresh = $attempt->fresh();
        $this->assertSame(ExamStatus::Completed, $fresh->status);
        $this->assertSame(8, $fresh->correct_count);
        $this->assertSame(1, $fresh->incorrect_count);
        $this->assertSame(1, $fresh->unanswered_count);
        $this->assertEquals(80.00, (float) $fresh->score_percentage);
    }

    public function test_cannot_modify_answers_after_submission(): void
    {
        $category = ExamCategory::firstOrFail();
        $attempt = $this->generatorService->generateCategoryPractice($this->user, $category, 5);
        $this->submissionService->submitExam($attempt);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot update answers on a completed examination.');

        $this->submissionService->saveAnswer($attempt, 1, 'A');
    }

    public function test_server_side_deadline_enforces_automatic_expiration(): void
    {
        $category = ExamCategory::firstOrFail();
        $attempt = $this->generatorService->generateCategoryPractice($this->user, $category, 5);

        // Simulate expired deadline
        $attempt->update(['deadline_at' => now()->subMinute()]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Examination time has expired');

        $this->submissionService->saveAnswer($attempt, 1, 'A');
        $this->assertSame(ExamStatus::Expired, $attempt->fresh()->status);
    }
}
