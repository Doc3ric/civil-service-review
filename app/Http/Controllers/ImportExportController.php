<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QuestionImportBatch;
use App\Services\Import\QuestionImportService;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    public function downloadTemplate(QuestionImportService $importService): StreamedResponse
    {
        $spreadsheet = $importService->generateTemplateSpreadsheet();

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="civil-service-question-template.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    public function downloadErrorReport(QuestionImportBatch $batch, QuestionImportService $importService): StreamedResponse
    {
        // Enforce user authorization check
        if ($batch->user_id !== null && $batch->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this import batch report.');
        }

        $spreadsheet = $importService->generateErrorReport($batch);

        $filename = "import-error-report-batch-{$batch->id}.xlsx";

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
