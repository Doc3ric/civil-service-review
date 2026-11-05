<?php

declare(strict_types=1);

namespace Tests\Feature\ExcelImport;

use App\Enums\CategoryCode;
use App\Enums\ImportRowStatus;
use App\Enums\ImportStatus;
use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\QuestionImportBatch;
use App\Models\User;
use App\Services\Import\DuplicateDetectionService;
use App\Services\Import\QuestionImportService;
use App\Services\Import\QuestionValidationService;
use Database\Seeders\ExamCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    private QuestionImportService $importService;
    private QuestionValidationService $validationService;
    private DuplicateDetectionService $duplicateService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExamCategorySeeder::class);
        $this->validationService = app(QuestionValidationService::class);
        $this->duplicateService = app(DuplicateDetectionService::class);
        $this->importService = app(QuestionImportService::class);
        $this->user = User::factory()->create();
    }

    public function test_template_generation_contains_expected_headers(): void
    {
        $spreadsheet = $this->importService->generateTemplateSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('category', $sheet->getCell('A1')->getValue());
        $this->assertSame('question', $sheet->getCell('B1')->getValue());
        $this->assertSame('choice_a', $sheet->getCell('C1')->getValue());
        $this->assertSame('choice_b', $sheet->getCell('D1')->getValue());
        $this->assertSame('choice_c', $sheet->getCell('E1')->getValue());
        $this->assertSame('choice_d', $sheet->getCell('F1')->getValue());
        $this->assertSame('correct_answer', $sheet->getCell('G1')->getValue());
        $this->assertSame('explanation', $sheet->getCell('H1')->getValue());
    }

    public function test_validation_service_validates_valid_and_invalid_rows(): void
    {
        // Valid row
        $validRow = [
            'category' => 'VERBAL',
            'question' => 'What is the antonym of LOQUACIOUS?',
            'choice_a' => 'Taciturn',
            'choice_b' => 'Garrulous',
            'choice_c' => 'Talkative',
            'choice_d' => 'Voluble',
            'correct_answer' => 'A',
            'explanation' => 'Taciturn means reserved.',
            'difficulty' => 'hard',
        ];

        $result = $this->validationService->validateRow($validRow);
        $this->assertTrue($result['is_valid']);
        $this->assertSame(CategoryCode::Verbal->value, $result['sanitized']['category_code']);
        $this->assertSame('A', $result['sanitized']['correct_answer']);

        // Invalid Category
        $invalidCatRow = $validRow;
        $invalidCatRow['category'] = 'ASTRONOMY';
        $catResult = $this->validationService->validateRow($invalidCatRow);
        $this->assertFalse($catResult['is_valid']);
        $this->assertStringContainsString('Invalid category', $catResult['errors'][0]);

        // Invalid Correct Answer
        $invalidKeyRow = $validRow;
        $invalidKeyRow['correct_answer'] = 'Z';
        $keyResult = $this->validationService->validateRow($invalidKeyRow);
        $this->assertFalse($keyResult['is_valid']);
        $this->assertStringContainsString('Correct answer must be A, B, C, or D', $keyResult['errors'][0]);
    }

    public function test_duplicate_detection_identifies_existing_question(): void
    {
        $category = ExamCategory::first();
        Question::create([
            'category_id' => $category->id,
            'question_text' => 'Unique test question content.',
            'correct_choice' => 'A',
            'content_hash' => Question::normalizeHash('Unique test question content.'),
        ]);

        $this->assertTrue($this->duplicateService->existsInDatabase('Unique test question content.'));
        $this->assertTrue($this->duplicateService->isDuplicate('Unique  test  question  content. '));
        $this->assertFalse($this->duplicateService->existsInDatabase('A completely different question.'));
    }

    public function test_can_parse_stage_and_execute_import_from_spreadsheet(): void
    {
        // Build a temporary Excel workbook with 2 valid rows and 1 invalid row
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['category', 'question', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct_answer', 'explanation', 'difficulty', 'source'];
        foreach ($headers as $idx => $h) {
            $col = chr(65 + $idx);
            $sheet->setCellValue("{$col}1", $h);
        }

        // Row 2: Valid
        $sheet->setCellValue('A2', 'VERBAL');
        $sheet->setCellValue('B2', 'Imported Test Question One?');
        $sheet->setCellValue('C2', 'Choice A');
        $sheet->setCellValue('D2', 'Choice B');
        $sheet->setCellValue('E2', 'Choice C');
        $sheet->setCellValue('F2', 'Choice D');
        $sheet->setCellValue('G2', 'A');
        $sheet->setCellValue('H2', 'Explanation text');

        // Row 3: Valid
        $sheet->setCellValue('A3', 'NUMERICAL');
        $sheet->setCellValue('B3', 'Imported Test Question Two?');
        $sheet->setCellValue('C3', '10');
        $sheet->setCellValue('D3', '20');
        $sheet->setCellValue('E3', '30');
        $sheet->setCellValue('F3', '40');
        $sheet->setCellValue('G3', 'B');

        // Row 4: Invalid (missing choices)
        $sheet->setCellValue('A4', 'NUMERICAL');
        $sheet->setCellValue('B4', 'Imported Test Question Three (Invalid)?');
        $sheet->setCellValue('C4', '10');
        $sheet->setCellValue('G4', 'B');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_import_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        // Stage import
        $batch = $this->importService->parseAndStageImport($tempPath, 'test_questions.xlsx', $this->user);

        $this->assertSame(3, $batch->total_rows);
        $this->assertSame(1, $batch->invalid_rows);
        $this->assertSame(0, $batch->duplicate_rows);
        $this->assertSame(ImportStatus::Preview, $batch->status);

        // Execute import
        $summary = $this->importService->executeImport($batch);

        $this->assertSame(2, $summary['imported']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertSame(ImportStatus::Completed, $batch->fresh()->status);

        // Assert database
        $this->assertDatabaseHas('questions', ['question_text' => 'Imported Test Question One?']);
        $this->assertDatabaseHas('questions', ['question_text' => 'Imported Test Question Two?']);
        $this->assertDatabaseMissing('questions', ['question_text' => 'Imported Test Question Three (Invalid)?']);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }
}
