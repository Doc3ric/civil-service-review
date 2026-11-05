<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Enums\CategoryCode;
use App\Enums\ImportRowStatus;
use App\Enums\ImportStatus;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportRow;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class QuestionImportService
{
    private const int CHUNK_SIZE = 250;

    public function __construct(
        private readonly QuestionValidationService $validationService,
        private readonly DuplicateDetectionService $duplicateService
    ) {}

    /**
     * Create a Question Import Template Spreadsheet (.xlsx).
     */
    public function generateTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Questions');

        $headers = [
            'category',
            'question',
            'choice_a',
            'choice_b',
            'choice_c',
            'choice_d',
            'correct_answer',
            'explanation',
            'difficulty',
            'source',
        ];

        // Write Headers
        foreach ($headers as $colIndex => $header) {
            $colLetter = chr(65 + $colIndex);
            $sheet->setCellValue("{$colLetter}1", $header);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Style Header Row: Navy Blue #174A8B with White Bold text
        $headerRange = 'A1:J1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '174A8B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Sample Rows
        $sampleRows = [
            [
                'VERBAL',
                'Choose the word most opposite in meaning to EPHEMERAL.',
                'Fleeting',
                'Permanent',
                'Transient',
                'Momentary',
                'B',
                'Ephemeral means fleeting or short-lived; its antonym is permanent.',
                'Medium',
                'Civil Service Reviewer 2027',
            ],
            [
                'ANALYTICAL',
                'SURGEON : SCALPEL ::',
                'Author : Book',
                'Sculptor : Chisel',
                'Carpenter : Wood',
                'Musician : Piano',
                'B',
                'A surgeon uses a scalpel as a primary tool; a sculptor uses a chisel.',
                'Easy',
                'Civil Service Reviewer 2027',
            ],
            [
                'NUMERICAL',
                'What is 35% of 2,400?',
                '720',
                '840',
                '860',
                '920',
                'B',
                '0.35 * 2400 = 840',
                'Easy',
                'Civil Service Reviewer 2027',
            ],
            [
                'GENERAL',
                'Under RA 6713, public officials must submit their annual SALN on or before which date?',
                'March 31',
                'April 30',
                'May 15',
                'December 31',
                'B',
                'Section 8 of RA 6713 mandates filing on or before April 30.',
                'Medium',
                'RA 6713 Reviewer',
            ],
        ];

        $rowNum = 2;
        foreach ($sampleRows as $row) {
            foreach ($row as $colIndex => $val) {
                $colLetter = chr(65 + $colIndex);
                $sheet->setCellValue("{$colLetter}{$rowNum}", $val);
            }
            $rowNum++;
        }

        return $spreadsheet;
    }

    /**
     * Process an uploaded file and create an import batch with preview rows.
     * Uses preloaded in-memory hash index and chunked multi-row inserts for speed.
     *
     * @param string $filePath
     * @param string $originalFilename
     * @param User|null $user
     * @return QuestionImportBatch
     */
    public function parseAndStageImport(string $filePath, string $originalFilename, ?User $user = null): QuestionImportBatch
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("Uploaded file not found at: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestCol = $sheet->getHighestDataColumn();

        // Read Header Row (Row 1)
        $headerData = $sheet->rangeToArray("A1:{$highestCol}1", null, true, false)[0];
        $headerMap = [];
        foreach ($headerData as $idx => $name) {
            if ($name !== null) {
                $clean = strtolower(trim((string) $name));
                $clean = str_replace([' ', '-'], '_', $clean);
                $headerMap[$clean] = $idx;
            }
        }

        // Required Columns Check
        $required = ['category', 'question', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct_answer'];
        $missing = [];
        foreach ($required as $col) {
            if (!array_key_exists($col, $headerMap)) {
                $missing[] = $col;
            }
        }

        if (count($missing) > 0) {
            throw new RuntimeException('Missing required columns: ' . implode(', ', $missing));
        }

        $this->duplicateService->reset();
        $this->duplicateService->preloadExistingHashes();

        return DB::transaction(function () use ($sheet, $highestRow, $headerMap, $originalFilename, $filePath, $user) {
            $batch = QuestionImportBatch::create([
                'user_id' => $user?->id,
                'original_filename' => $originalFilename,
                'storage_path' => $filePath,
                'total_rows' => 0,
                'imported_rows' => 0,
                'invalid_rows' => 0,
                'duplicate_rows' => 0,
                'skipped_rows' => 0,
                'status' => ImportStatus::Preview->value,
            ]);

            $total = 0;
            $invalid = 0;
            $duplicates = 0;
            $valid = 0;
            $rowBuffer = [];
            $now = Carbon::now()->toDateTimeString();

            for ($row = 2; $row <= $highestRow; $row++) {
                $getVal = function (string $key) use ($sheet, $headerMap, $row): string {
                    if (!isset($headerMap[$key])) {
                        return '';
                    }
                    $colIndex = $headerMap[$key] + 1;
                    $val = $sheet->getCell([$colIndex, $row])->getFormattedValue();
                    return $val !== null ? trim((string) $val) : '';
                };

                $rowData = [
                    'category' => $getVal('category'),
                    'question' => $getVal('question'),
                    'choice_a' => $getVal('choice_a'),
                    'choice_b' => $getVal('choice_b'),
                    'choice_c' => $getVal('choice_c'),
                    'choice_d' => $getVal('choice_d'),
                    'correct_answer' => $getVal('correct_answer'),
                    'explanation' => $getVal('explanation'),
                    'difficulty' => $getVal('difficulty'),
                    'source' => $getVal('source'),
                ];

                // Skip completely empty rows
                $hasContent = false;
                foreach ($rowData as $v) {
                    if ($v !== '') {
                        $hasContent = true;
                        break;
                    }
                }
                if (!$hasContent) {
                    continue;
                }

                $total++;
                $validation = $this->validationService->validateRow($rowData);

                $status = ImportRowStatus::Valid;
                $errors = $validation['errors'];

                if (!$validation['is_valid']) {
                    $status = ImportRowStatus::Invalid;
                    $invalid++;
                } else {
                    $questionText = (string) $validation['sanitized']['question_text'];
                    if ($this->duplicateService->isDuplicate($questionText)) {
                        $status = ImportRowStatus::Duplicate;
                        $errors[] = 'Duplicate question detected (already exists in Question Bank or in this file).';
                        $duplicates++;
                    } else {
                        $valid++;
                    }
                }

                $rowBuffer[] = [
                    'import_batch_id' => $batch->id,
                    'row_number' => $row,
                    'raw_data_json' => json_encode($rowData),
                    'category_code' => $validation['sanitized']['category_code'] ?? ($rowData['category'] ?: null),
                    'question_text' => $validation['sanitized']['question_text'] ?? ($rowData['question'] ?: null),
                    'choice_a' => $validation['sanitized']['choice_a'] ?? ($rowData['choice_a'] ?: null),
                    'choice_b' => $validation['sanitized']['choice_b'] ?? ($rowData['choice_b'] ?: null),
                    'choice_c' => $validation['sanitized']['choice_c'] ?? ($rowData['choice_c'] ?: null),
                    'choice_d' => $validation['sanitized']['choice_d'] ?? ($rowData['choice_d'] ?: null),
                    'correct_answer' => $validation['sanitized']['correct_answer'] ?? ($rowData['correct_answer'] ?: null),
                    'difficulty' => $validation['sanitized']['difficulty'] ?? ($rowData['difficulty'] ?: null),
                    'source' => $validation['sanitized']['source'] ?? ($rowData['source'] ?: null),
                    'status' => $status->value,
                    'error_messages_json' => count($errors) > 0 ? json_encode($errors) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($rowBuffer) >= self::CHUNK_SIZE) {
                    QuestionImportRow::insert($rowBuffer);
                    $rowBuffer = [];
                }
            }

            if (count($rowBuffer) > 0) {
                QuestionImportRow::insert($rowBuffer);
            }

            $batch->update([
                'total_rows' => $total,
                'invalid_rows' => $invalid,
                'duplicate_rows' => $duplicates,
            ]);

            return $batch->fresh(['rows']);
        });
    }

    /**
     * Confirm and execute import of all valid rows from a staged batch.
     * Processes in chunks with bulk choice creation and atomic transactions.
     *
     * @param QuestionImportBatch $batch
     * @return array{
     *     total: int,
     *     imported: int,
     *     skipped: int,
     *     duplicates: int,
     *     invalid: int
     * }
     */
    public function executeImport(QuestionImportBatch $batch): array
    {
        $categories = ExamCategory::all()->keyBy('code');

        return DB::transaction(function () use ($batch, $categories) {
            $importedCount = 0;
            $now = Carbon::now()->toDateTimeString();

            $batch->rows()
                ->where('status', ImportRowStatus::Valid->value)
                ->chunkById(self::CHUNK_SIZE, function ($rows) use ($batch, $categories, &$importedCount, $now) {
                    $importedRowIds = [];
                    $choicesToInsert = [];

                    foreach ($rows as $row) {
                        $catCode = $row->category_code;
                        $category = $categories->get($catCode);
                        if ($category === null) {
                            $row->update([
                                'status' => ImportRowStatus::Invalid->value,
                                'error_messages_json' => ["Category '{$catCode}' not found."],
                            ]);
                            continue;
                        }

                        $hash = Question::normalizeHash((string) $row->question_text);

                        $question = Question::create([
                            'category_id' => $category->id,
                            'import_batch_id' => $batch->id,
                            'question_text' => (string) $row->question_text,
                            'correct_choice' => (string) $row->correct_answer,
                            'explanation' => $row->raw_data_json['explanation'] ?? null,
                            'difficulty' => $row->difficulty ?? QuestionDifficulty::Medium->value,
                            'source' => $row->source ?? $batch->original_filename,
                            'status' => QuestionStatus::Published->value,
                            'content_hash' => $hash,
                        ]);

                        $choices = [
                            'A' => $row->choice_a,
                            'B' => $row->choice_b,
                            'C' => $row->choice_c,
                            'D' => $row->choice_d,
                        ];

                        foreach ($choices as $lbl => $text) {
                            $choicesToInsert[] = [
                                'question_id' => $question->id,
                                'choice_label' => $lbl,
                                'choice_text' => (string) $text,
                                'is_correct' => ($lbl === $question->correct_choice),
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }

                        $importedRowIds[] = $row->id;
                        $importedCount++;
                    }

                    if (count($choicesToInsert) > 0) {
                        QuestionChoice::insert($choicesToInsert);
                    }

                    if (count($importedRowIds) > 0) {
                        QuestionImportRow::whereIn('id', $importedRowIds)->update([
                            'status' => ImportRowStatus::Imported->value,
                            'updated_at' => $now,
                        ]);
                    }
                });

            // Mark remaining non-valid rows as skipped
            $batch->rows()
                ->whereIn('status', [ImportRowStatus::Invalid->value, ImportRowStatus::Duplicate->value])
                ->update(['status' => ImportRowStatus::Skipped->value]);

            $skippedCount = $batch->total_rows - $importedCount;

            $batch->update([
                'imported_rows' => $importedCount,
                'skipped_rows' => $skippedCount,
                'status' => ImportStatus::Completed->value,
            ]);

            return [
                'total' => $batch->total_rows,
                'imported' => $importedCount,
                'skipped' => $skippedCount,
                'duplicates' => $batch->duplicate_rows,
                'invalid' => $batch->invalid_rows,
            ];
        });
    }

    /**
     * Generate an Excel error report workbook for a batch.
     */
    public function generateErrorReport(QuestionImportBatch $batch): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Errors');

        $headers = [
            'Excel Row #',
            'Category',
            'Question',
            'Correct Answer',
            'Status',
            'Error Messages',
        ];

        foreach ($headers as $colIndex => $header) {
            $colLetter = chr(65 + $colIndex);
            $sheet->setCellValue("{$colLetter}1", $header);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C92535'], // Primary Red
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $rows = $batch->rows()
            ->whereNotNull('error_messages_json')
            ->orderBy('row_number')
            ->get();

        $line = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$line}", $row->row_number);
            $sheet->setCellValue("B{$line}", $row->category_code ?? '');
            $sheet->setCellValue("C{$line}", $row->question_text ?? '');
            $sheet->setCellValue("D{$line}", $row->correct_answer ?? '');
            $sheet->setCellValue("E{$line}", $row->status->label());
            $sheet->setCellValue("F{$line}", implode(' | ', $row->error_messages_json ?? []));
            $line++;
        }

        return $spreadsheet;
    }
}
