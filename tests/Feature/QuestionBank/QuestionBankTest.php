<?php

declare(strict_types=1);

namespace Tests\Feature\QuestionBank;

use App\Enums\CategoryCode;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionOrigin;
use App\Enums\QuestionStatus;
use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\User;
use App\Services\QuestionBank\QuestionManagementService;
use App\Services\QuestionBank\QuestionTextFormatter;
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

    public function test_question_editor_saves_bold_text_and_renders_it_safely(): void
    {
        $question = $this->service->createQuestion([
            'category_id' => $this->verbalCategory->id,
            'question_text' => 'What is the synonym of candid?',
            'choices' => ['A' => 'Frank', 'B' => 'Quiet', 'C' => 'Hidden', 'D' => 'Late'],
            'correct_choice' => 'A',
        ]);

        \Livewire\Livewire::actingAs($this->user)
            ->test(\App\Livewire\Questions\QuestionList::class)
            ->call('openEditModal', $question->id)
            ->assertSee('Bold selected question text')
            ->set('formQuestionText', 'What is the synonym of **candid**?')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $updatedQuestion = $question->fresh();
        $this->assertSame('What is the synonym of **candid**?', $updatedQuestion->question_text);
        $this->assertSame(Question::normalizeHash('What is the synonym of candid?'), $updatedQuestion->content_hash);
        $this->assertSame('What is the synonym of candid?', QuestionTextFormatter::toPlainText($updatedQuestion->question_text));
        $this->assertSame(
            'What is the synonym of <strong>candid</strong>?',
            QuestionTextFormatter::toHtml($updatedQuestion->question_text),
        );
        $this->assertSame('&lt;script&gt;<strong>candid</strong>&lt;/script&gt;', QuestionTextFormatter::toHtml('<script>**candid**</script>'));
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
    }

    public function test_delete_all_confirmation_removes_questions_through_livewire(): void
    {
        $question = $this->service->createQuestion([
            'category_id' => $this->verbalCategory->id,
            'question_text' => 'Question deleted through the confirmation dialog?',
            'choices' => ['A' => 'One', 'B' => 'Two', 'C' => 'Three', 'D' => 'Four'],
            'correct_choice' => 'A',
        ]);

        \Livewire\Livewire::actingAs($this->user)
            ->test(\App\Livewire\Questions\QuestionList::class)
            ->call('confirmDeleteAll')
            ->assertSet('showDeleteAllModal', true)
            ->call('deleteAllQuestions')
            ->assertHasNoErrors()
            ->assertSet('showDeleteAllModal', false);

        $this->assertNull(Question::withTrashed()->find($question->id));
        $this->assertSame(0, \App\Models\QuestionChoice::count());
    }

    public function test_removing_sample_questions_keeps_manual_questions(): void
    {
        $sampleQuestion = $this->service->createQuestion([
            'category_id' => $this->verbalCategory->id,
            'question_text' => 'Built-in sample question to remove',
            'choices' => ['A' => 'One', 'B' => 'Two', 'C' => 'Three', 'D' => 'Four'],
            'correct_choice' => 'A',
            'origin' => QuestionOrigin::Sample->value,
        ]);
        $manualQuestion = $this->service->createQuestion([
            'category_id' => $this->verbalCategory->id,
            'question_text' => 'Trusted manually entered question',
            'choices' => ['A' => 'One', 'B' => 'Two', 'C' => 'Three', 'D' => 'Four'],
            'correct_choice' => 'B',
        ]);
        $sampleQuestion->delete();

        $this->assertSame(1, $this->service->deleteSampleQuestions());
        $this->assertNull(Question::withTrashed()->find($sampleQuestion->id));
        $this->assertDatabaseHas('questions', ['id' => $manualQuestion->id, 'origin' => QuestionOrigin::Manual->value]);
    }

    public function test_can_create_and_update_question_with_explanation_image(): void
    {
        $question = $this->service->createQuestion([
            'category_id' => $this->verbalCategory->id,
            'question_text' => 'Question with explanation diagram',
            'choices' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4'],
            'correct_choice' => 'A',
            'explanation' => 'See diagram for step-by-step resolution.',
            'explanation_image' => 'explanations/test_diagram.png',
        ]);

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'explanation_image' => 'explanations/test_diagram.png',
        ]);
        $this->assertNotNull($question->explanation_image_url);
        $this->assertStringContainsString('storage/explanations/test_diagram.png', $question->explanation_image_url);

        // Update with new image
        $this->service->updateQuestion($question, [
            'explanation_image' => 'explanations/updated_diagram.png',
        ]);

        $this->assertSame('explanations/updated_diagram.png', $question->fresh()->explanation_image);
    }

    public function test_livewire_can_upload_explanation_image_when_saving_question(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $fakeImage = \Illuminate\Http\UploadedFile::fake()->image('math_solution.png', 600, 400);

        \Livewire\Livewire::actingAs($this->user)
            ->test(\App\Livewire\Questions\QuestionList::class)
            ->call('openCreateModal')
            ->set('formCategoryId', $this->verbalCategory->id)
            ->set('formQuestionText', 'What is the value of x in the diagram?')
            ->set('formChoiceA', '10')
            ->set('formChoiceB', '20')
            ->set('formChoiceC', '30')
            ->set('formChoiceD', '40')
            ->set('formCorrectChoice', 'B')
            ->set('formExplanation', 'Refer to the uploaded geometric triangle diagram.')
            ->set('formExplanationImage', $fakeImage)
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $created = Question::where('question_text', 'What is the value of x in the diagram?')->first();
        $this->assertNotNull($created);
        $this->assertNotNull($created->explanation_image);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($created->explanation_image);
    }}
