<?php

declare(strict_types=1);

namespace Tests\Feature\Examination;

use App\Enums\ExamStatus;
use App\Livewire\Examinations\ExamRunner;
use App\Models\ExamAnswer;
use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\StudyActivity;
use App\Models\User;
use App\Services\Examination\ExamGeneratorService;
use App\Services\Examination\ExamSubmissionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExaminationReliabilityTest extends TestCase
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

    public function test_browser_refresh_recovers_active_examination_state(): void
    {
        $category = ExamCategory::firstOrFail();
        $attempt = $this->generatorService->generateCategoryPractice($this->user, $category, 10);

        // Answer items 1, 2, and 5
        $this->submissionService->saveAnswer($attempt, 1, 'A');
        $this->submissionService->saveAnswer($attempt, 2, 'B');
        $this->submissionService->saveAnswer($attempt, 5, 'D');

        // Simulate browser refresh by loading Livewire ExamRunner
        Livewire::actingAs($this->user)
            ->test(ExamRunner::class, ['attempt' => $attempt->id])
            ->assertSet('attemptId', $attempt->id)
            ->assertSet('userAnswers.1', 'A')
            ->assertSet('userAnswers.2', 'B')
            ->assertSet('userAnswers.5', 'D')
            ->assertSet('userAnswers.3', null);
    }

    public function test_simultaneous_and_repeated_answer_updates_are_idempotent(): void
    {
        $category = ExamCategory::firstOrFail();
        $attempt = $this->generatorService->generateCategoryPractice($this->user, $category, 5);

        // Rapidly change answers on the same question
        $this->submissionService->saveAnswer($attempt, 1, 'A');
        $this->submissionService->saveAnswer($attempt, 1, 'B');
        $this->submissionService->saveAnswer($attempt, 1, 'C');
        $finalAnswer = $this->submissionService->saveAnswer($attempt, 1, 'D');

        $this->assertSame('D', $finalAnswer->selected_choice_label);

        // Verify database only has 1 record for this attempt-question pairing
        $attemptQuestion = $attempt->attemptQuestions()->where('question_number', 1)->first();
        $count = ExamAnswer::where('exam_attempt_id', $attempt->id)
            ->where('exam_attempt_question_id', $attemptQuestion->id)
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_duplicate_submission_is_prevented_and_idempotent(): void
    {
        $category = ExamCategory::firstOrFail();
        $attempt = $this->generatorService->generateCategoryPractice($this->user, $category, 5);

        // Answer question 1
        $this->submissionService->saveAnswer($attempt, 1, 'A');

        // First submission
        $result1 = $this->submissionService->submitExam($attempt);
        $this->assertSame(5, $result1['total']);

        // Second simultaneous submission
        $result2 = $this->submissionService->submitExam($attempt);
        $this->assertSame($result1['percentage'], $result2['percentage']);

        // Verify study activities table only has 1 record for this attempt
        $activityCount = StudyActivity::where('exam_attempt_id', $attempt->id)->count();
        $this->assertSame(1, $activityCount);
    }

    public function test_deadline_expiration_is_authoritative_and_auto_submits(): void
    {
        $category = ExamCategory::firstOrFail();
        $attempt = $this->generatorService->generateCategoryPractice($this->user, $category, 5);

        // Backdate deadline into past
        $attempt->update(['deadline_at' => now()->subMinutes(5)]);

        // Livewire action triggers expired handler
        Livewire::actingAs($this->user)
            ->test(ExamRunner::class, ['attempt' => $attempt->id])
            ->call('handleTimeExpired')
            ->assertRedirect(route('results.show', ['attempt' => $attempt->id]));

        $fresh = $attempt->fresh();
        $this->assertSame(ExamStatus::Expired, $fresh->status);
        $this->assertNotNull($fresh->submitted_at);
    }

    public function test_historical_snapshots_remain_immutable_when_question_bank_is_altered(): void
    {
        $category = ExamCategory::firstOrFail();
        $attempt = $this->generatorService->generateCategoryPractice($this->user, $category, 5);

        $attemptQuestion = $attempt->attemptQuestions()->where('question_number', 1)->first();
        $originalSnapshotPrompt = $attemptQuestion->question_text_snapshot;
        $originalMasterQuestion = Question::find($attemptQuestion->question_id);

        // Alter master question in Question Bank
        $originalMasterQuestion->update([
            'question_text' => 'MODIFIED PROMPT IN MASTER BANK - SHOULD NOT AFFECT SNAPSHOT',
            'status' => 'archived',
        ]);

        // Verify immutable snapshot on attempt question is completely unchanged
        $this->assertSame($originalSnapshotPrompt, $attemptQuestion->fresh()->question_text_snapshot);
        $this->assertNotSame($originalMasterQuestion->fresh()->question_text, $attemptQuestion->fresh()->question_text_snapshot);
    }
}
