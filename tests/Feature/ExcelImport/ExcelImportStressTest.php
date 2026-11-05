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
use App\Services\Import\QuestionImportService;
use Database\Seeders\ExamCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelImportStressTest extends TestCase
{
    use RefreshDatabase;

    private QuestionImportService $importService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExamCategorySeeder::class);
        $this->importService = app(QuestionImportService::class);
        $this->user = User::factory()->create();
    }

    public function test_import_with_100_questions_performance(): void
    {
        $filePath = $this->generateWorkbookWithQuestions(100);

        $startMem = memory_get_usage();
        $startTime = microtime(true);

        $batch = $this->importService->parseAndStageImport($filePath, 'stress_100.xlsx', $this->user);
        $result = $this->importService->executeImport($batch);

        $elapsed = microtime(true) - $startTime;
        $memUsed = (memory_get_usage() - $startMem) / 1024 / 1024;

        $this->assertSame(100, $result['imported']);
        $this->assertSame(0, $result['invalid']);
        $this->assertSame(0, $result['duplicates']);
        $this->assertLessThan(10.0, $elapsed, "100 questions took {$elapsed}s (should be < 10s)");

        @unlink($filePath);
    }

    public function test_import_with_500_questions_performance(): void
    {
        $filePath = $this->generateWorkbookWithQuestions(500);

        $startTime = microtime(true);

        $batch = $this->importService->parseAndStageImport($filePath, 'stress_500.xlsx', $this->user);
        $result = $this->importService->executeImport($batch);

        $elapsed = microtime(true) - $startTime;

        $this->assertSame(500, $result['imported']);
        $this->assertSame(0, $result['invalid']);
        $this->assertLessThan(20.0, $elapsed, "500 questions took {$elapsed}s (should be < 20s)");

        @unlink($filePath);
    }

    public function test_import_with_1000_questions_performance(): void
    {
        $filePath = $this->generateWorkbookWithQuestions(1000);

        $startTime = microtime(true);

        $batch = $this->importService->parseAndStageImport($filePath, 'stress_1000.xlsx', $this->user);
        $result = $this->importService->executeImport($batch);

        $elapsed = microtime(true) - $startTime;

        $this->assertSame(1000, $result['imported']);
        $this->assertLessThan(35.0, $elapsed, "1000 questions took {$elapsed}s (should be < 35s)");

        @unlink($filePath);
    }

    public function test_edge_cases_dirty_input_and_special_characters(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['category', 'question', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct_answer', 'explanation', 'difficulty', 'source'];
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue(chr(65 + $idx) . '1', $h);
        }

        // Row 2: Special Characters (Philippine ñ, symbols, quotes)
        $sheet->setCellValue('A2', 'verbal'); // Lowercase category
        $sheet->setCellValue('B2', 'Anong ibig sabihin ng "Kagawaran ng Edukasyon" sa konteksto ng ika-19 na siglo? May letrang Ñ at simbolo: 100% & ₱500.');
        $sheet->setCellValue('C2', 'Opsiyon A (may "quotes")');
        $sheet->setCellValue('D2', 'Opsiyon B (may \'single\')');
        $sheet->setCellValue('E2', 'Opsiyon C');
        $sheet->setCellValue('F2', 'Opsiyon D');
        $sheet->setCellValue('G2', 'a'); // Lowercase answer key

        // Row 3: Irregular Whitespace
        $sheet->setCellValue('A3', '   ANALYTICAL   ');
        $sheet->setCellValue('B3', "   This   question   has    excessive   internal   and  trailing   whitespace?   \n\r");
        $sheet->setCellValue('C3', '  Option A  ');
        $sheet->setCellValue('D3', '  Option B  ');
        $sheet->setCellValue('E3', '  Option C  ');
        $sheet->setCellValue('F3', '  Option D  ');
        $sheet->setCellValue('G3', 'B');

        // Row 4: Duplicate of Row 3 with different spacing
        $sheet->setCellValue('A4', 'ANALYTICAL');
        $sheet->setCellValue('B4', 'This question has excessive internal and trailing whitespace?');
        $sheet->setCellValue('C4', 'Option A');
        $sheet->setCellValue('D4', 'Option B');
        $sheet->setCellValue('E4', 'Option C');
        $sheet->setCellValue('F4', 'Option D');
        $sheet->setCellValue('G4', 'B');

        // Row 5: Long question text (over 1,000 chars)
        $longText = str_repeat('The Philippine Civil Service Commission is mandated to enforce merit and fitness. ', 20);
        $sheet->setCellValue('A5', 'GENERAL');
        $sheet->setCellValue('B5', $longText);
        $sheet->setCellValue('C5', 'Choice A');
        $sheet->setCellValue('D5', 'Choice B');
        $sheet->setCellValue('E5', 'Choice C');
        $sheet->setCellValue('F5', 'Choice D');
        $sheet->setCellValue('G5', 'C');

        // Row 6: Invalid category
        $sheet->setCellValue('A6', 'ROCKET_SCIENCE');
        $sheet->setCellValue('B6', 'What is astrophysics?');
        $sheet->setCellValue('C6', 'A');
        $sheet->setCellValue('D6', 'B');
        $sheet->setCellValue('E6', 'C');
        $sheet->setCellValue('F6', 'D');
        $sheet->setCellValue('G6', 'A');

        $tempPath = tempnam(sys_get_temp_dir(), 'edge_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $batch = $this->importService->parseAndStageImport($tempPath, 'edge_cases.xlsx', $this->user);

        $this->assertSame(5, $batch->total_rows);
        $this->assertSame(1, $batch->invalid_rows); // ROCKET_SCIENCE is invalid
        $this->assertSame(1, $batch->duplicate_rows); // Row 4 duplicate of Row 3

        $result = $this->importService->executeImport($batch);

        $this->assertSame(3, $result['imported']); // Rows 2, 3, 5 imported
        $this->assertSame(2, $result['skipped']); // Rows 4, 6 skipped

        // Verify special characters survived intact
        $qSpecial = Question::where('question_text', 'like', '%May letrang Ñ%')->first();
        $this->assertNotNull($qSpecial);
        $this->assertSame('A', $qSpecial->correct_choice);

        @unlink($tempPath);
    }

    private function generateWorkbookWithQuestions(int $count): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['category', 'question', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct_answer', 'explanation', 'difficulty', 'source'];
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue(chr(65 + $idx) . '1', $h);
        }

        $categories = ['VERBAL', 'ANALYTICAL', 'NUMERICAL', 'GENERAL'];
        $keys = ['A', 'B', 'C', 'D'];

        for ($i = 1; $i <= $count; $i++) {
            $row = $i + 1;
            $cat = $categories[$i % count($categories)];
            $key = $keys[$i % count($keys)];

            $sheet->setCellValue("A{$row}", $cat);
            $sheet->setCellValue("B{$row}", "Automated Stress Test Item #{$i}: What is the evaluation of item index {$i}?");
            $sheet->setCellValue("C{$row}", "Choice A for item {$i}");
            $sheet->setCellValue("D{$row}", "Choice B for item {$i}");
            $sheet->setCellValue("E{$row}", "Choice C for item {$i}");
            $sheet->setCellValue("F{$row}", "Choice D for item {$i}");
            $sheet->setCellValue("G{$row}", $key);
            $sheet->setCellValue("H{$row}", "Explanation for item {$i}");
            $sheet->setCellValue("I{$row}", "Medium");
            $sheet->setCellValue("J{$row}", "Stress Test Suite");
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'stress_test_' . $count . '_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }
}
