<?php

declare(strict_types=1);

namespace Tests\Feature\Examination;

use App\Enums\CategoryCode;
use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Models\ExamBlueprint;
use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\User;
use App\Services\Examination\ChoiceRandomizerService;
use App\Services\Examination\ExamGeneratorService;
use App\Services\Examination\QuestionSelectionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ExaminationGenerationTest extends TestCase
{
    use RefreshDatabase;

    private ExamGeneratorService $generatorService;
    private QuestionSelectionService $selectionService;
    private ChoiceRandomizerService $randomizerService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->generatorService = app(ExamGeneratorService::class);
        $this->selectionService = app(QuestionSelectionService::class);
        $this->randomizerService = app(ChoiceRandomizerService::class);
        $this->user = User::firstOrFail();
    }

    public function test_full_exam_generates_exactly_150_questions(): void
    {
        $blueprint = ExamBlueprint::where('is_active', true)->firstOrFail();

        $attempt = $this->generatorService->generateFullExam($this->user, $blueprint);

        $this->assertSame(150, $attempt->total_questions);
        $this->assertCount(150, $attempt->attemptQuestions);
        $this->assertSame(ExamMode::Full, $attempt->mode);
        $this->assertSame(ExamStatus::InProgress, $attempt->status);
        $this->assertNotNull($attempt->deadline_at);
        $this->assertTrue($attempt->deadline_at->greaterThan($attempt->started_at));
    }

    public function test_full_exam_matches_exact_category_distribution(): void
    {
        $blueprint = ExamBlueprint::where('is_active', true)->firstOrFail();
        $attempt = $this->generatorService->generateFullExam($this->user, $blueprint);

        $questions = $attempt->attemptQuestions;

        $verbalCat = ExamCategory::where('code', CategoryCode::Verbal->value)->first();
        $analyticalCat = ExamCategory::where('code', CategoryCode::Analytical->value)->first();
        $numericalCat = ExamCategory::where('code', CategoryCode::Numerical->value)->first();
        $generalCat = ExamCategory::where('code', CategoryCode::General->value)->first();

        $this->assertSame(45, $questions->where('category_id', $verbalCat->id)->count());
        $this->assertSame(53, $questions->where('category_id', $analyticalCat->id)->count());
        $this->assertSame(45, $questions->where('category_id', $numericalCat->id)->count());
        $this->assertSame(7, $questions->where('category_id', $generalCat->id)->count());
    }

    public function test_no_duplicate_questions_in_single_attempt(): void
    {
        $blueprint = ExamBlueprint::where('is_active', true)->firstOrFail();
        $attempt = $this->generatorService->generateFullExam($this->user, $blueprint);

        $questionIds = $attempt->attemptQuestions->pluck('question_id')->all();
        $this->assertCount(150, array_unique($questionIds));
    }

    public function test_choice_randomizer_preserves_correct_answer_mapping(): void
    {
        $question = Question::with('choices')->firstOrFail();
        $originalCorrectChoice = $question->choices->firstWhere('is_correct', true);

        $randomized = $this->randomizerService->randomizeChoices($question);

        $this->assertCount(4, $randomized['shuffled_choices']);
        $newCorrectLabel = $randomized['correct_choice_label'];

        $matchingChoice = collect($randomized['shuffled_choices'])->firstWhere('label', $newCorrectLabel);
        $this->assertNotNull($matchingChoice);
        $this->assertTrue($matchingChoice['is_correct']);
        $this->assertSame($originalCorrectChoice->choice_text, $matchingChoice['text']);
    }

    public function test_category_practice_generates_configured_item_count(): void
    {
        $category = ExamCategory::where('code', CategoryCode::Verbal->value)->firstOrFail();
        $attempt = $this->generatorService->generateCategoryPractice($this->user, $category, 10);

        $this->assertSame(10, $attempt->total_questions);
        $this->assertCount(10, $attempt->attemptQuestions);
        $this->assertSame(ExamMode::Category, $attempt->mode);
    }

    public function test_cannot_generate_exam_if_insufficient_published_questions(): void
    {
        // Delete all General category questions to create a deficit
        $generalCat = ExamCategory::where('code', CategoryCode::General->value)->firstOrFail();
        Question::where('category_id', $generalCat->id)->forceDelete();

        $blueprint = ExamBlueprint::where('is_active', true)->firstOrFail();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient published questions');

        $this->generatorService->generateFullExam($this->user, $blueprint);
    }
}
