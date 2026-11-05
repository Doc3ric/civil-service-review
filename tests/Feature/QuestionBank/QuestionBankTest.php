<?php

declare(strict_types=1);

namespace Tests\Feature\QuestionBank;

use App\Enums\CategoryCode;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\User;
use App\Services\QuestionBank\QuestionManagementService;
use Database\Seeders\ExamCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    private QuestionManagementService $service;
    private ExamCategory $verbalCategory;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExamCategorySeeder::class);
        $this->service = app(QuestionManagementService::class);
        $this->verbalCategory = ExamCategory::where('code', CategoryCode::Verbal->value)->firstOrFail();
        $this->user = User::factory()->create();
    }

    public function test_can_create_question_with_four_choices_and_correct_answer(): void
    {
        $question = $this->service->createQuestion([
            'category_id' => $this->verbalCategory->id,
            'question_text' => 'What is the synonym of CANDID?',
            'choices' => [
                'A' => 'Frank',
                'B' => 'Deceptive',
                'C' => 'Secretive',
                'D' => 'Reserved',
            ],
            'correct_choice' => 'A',
            'explanation' => 'Candid means frank, open, and sincere.',
            'difficulty' => QuestionDifficulty::Medium->value,
            'source' => 'CSC Verbal Reviewer',
            'status' => QuestionStatus::Published->value,
        ]);

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_text' => 'What is the synonym of CANDID?',
            'correct_choice' => 'A',
        ]);

        $this->assertCount(4, $question->choices);
        $this->assertTrue($question->choices->firstWhere('choice_label', 'A')->is_correct);
        $this->assertFalse($question->choices->firstWhere('choice_label', 'B')->is_correct);
    }

    public function test_cannot_create_question_with_missing_choices(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->createQuestion([
            'category_id' => $this->verbalCategory->id,
            'question_text' => 'Sample Question with missing choice?',
            'choices' => [
                'A' => 'Choice A',
                'B' => 'Choice B',
                'C' => '', // Empty choice
                'D' => 'Choice D',
            ],
            'correct_choice' => 'A',
        ]);
    }

    public function test_cannot_create_question_with_invalid_correct_choice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->createQuestion([
            'category_id' => $this->verbalCategory->id,
            'question_text' => 'Sample Question with invalid key?',
            'choices' => [
                'A' => 'Choice A',
                'B' => 'Choice B',
                'C' => 'Choice C',
                'D' => 'Choice D',
            ],
            'correct_choice' => 'E', // Invalid
        ]);
    }

    public function test_can_filter_questions_by_category_and_difficulty(): void
    {
        $analyticalCategory = ExamCategory::where('code', CategoryCode::Analytical->value)->firstOrFail();

        $this->service->createQuestion([
            'category_id' => $this->verbalCategory->id,
            'question_text' => 'Verbal easy item',
            'choices' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4'],
            'correct_choice' => 'A',
            'difficulty' => QuestionDifficulty::Easy->value,
        ]);

        $this->service->createQuestion([
            'category_id' => $analyticalCategory->id,
            'question_text' => 'Analytical hard item',
            'choices' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4'],
            'correct_choice' => 'B',
            'difficulty' => QuestionDifficulty::Hard->value,
        ]);

        $verbalItems = $this->service->getPaginatedQuestions(['category_id' => $this->verbalCategory->id]);
        $this->assertCount(1, $verbalItems);
        $this->assertSame('Verbal easy item', $verbalItems->first()->question_text);

        $hardItems = $this->service->getPaginatedQuestions(['difficulty' => QuestionDifficulty::Hard->value]);
        $this->assertCount(1, $hardItems);
        $this->assertSame('Analytical hard item', $hardItems->first()->question_text);
    }

    public function test_can_archive_and_restore_question(): void
    {
        $question = $this->service->createQuestion([
            'category_id' => $this->verbalCategory->id,
            'question_text' => 'Question to archive',
            'choices' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4'],
            'correct_choice' => 'A',
            'status' => QuestionStatus::Published->value,
        ]);

        $this->service->archiveQuestion($question);
        $this->assertSame(QuestionStatus::Archived, $question->fresh()->status);

        $this->service->restoreQuestion($question);
        $this->assertSame(QuestionStatus::Published, $question->fresh()->status);
    }

    public function test_delete_all_questions_removes_every_question_and_choice(): void
    {
        $analyticalCategory = ExamCategory::where('code', CategoryCode::Analytical->value)->firstOrFail();

        // Seed a few questions across two categories
        foreach (range(1, 3) as $i) {
            $this->service->createQuestion([
                'category_id' => $this->verbalCategory->id,
                'question_text' => "Verbal question #{$i} to be deleted",
                'choices' => ['A' => 'Opt A', 'B' => 'Opt B', 'C' => 'Opt C', 'D' => 'Opt D'],
                'correct_choice' => 'A',
            ]);
        }
        foreach (range(1, 2) as $i) {
            $this->service->createQuestion([
                'category_id' => $analyticalCategory->id,
                'question_text' => "Analytical question #{$i} to be deleted",
                'choices' => ['A' => 'Opt A', 'B' => 'Opt B', 'C' => 'Opt C', 'D' => 'Opt D'],
                'correct_choice' => 'B',
            ]);
        }

        $this->assertSame(5, Question::count());
        $this->assertSame(20, \App\Models\QuestionChoice::count()); // 5 questions × 4 choices

        $deleted = $this->service->deleteAllQuestions();

        $this->assertSame(5, $deleted);
        $this->assertSame(0, Question::count());
        $this->assertSame(0, \App\Models\QuestionChoice::count());
    }}
