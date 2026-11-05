<?php

declare(strict_types=1);

namespace App\Livewire\Questions;

use App\Enums\ImportRowStatus;
use App\Enums\ImportStatus;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportRow;
use App\Services\Import\QuestionImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class QuestionImportManager extends Component
{
    use WithFileUploads;

    public $file = null;
    public ?int $activeBatchId = null;
    public string $rowFilter = 'all'; // all, valid, invalid, duplicate
    public bool $isImporting = false;
    public ?array $importSummary = null;

    public function mount(?int $batch = null): void
    {
        if ($batch !== null) {
            $existing = QuestionImportBatch::find($batch);
            if ($existing) {
                $this->activeBatchId = $existing->id;
            }
        }
    }

    public function processUpload(QuestionImportService $importService): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'], // max 10MB
        ]);

        $originalName = $this->file->getClientOriginalName();
        $storedPath = $this->file->storeAs('imports', uniqid('import_') . '_' . $originalName, 'local');
        $fullPath = storage_path('app/private/' . $storedPath);

        // Fallback check if default storage path is app/
        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/' . $storedPath);
        }

        try {
            $batch = $importService->parseAndStageImport($fullPath, $originalName, Auth::user());
            $this->activeBatchId = $batch->id;
            $this->importSummary = null;
            $this->file = null;
            session()->flash('success', "File '{$originalName}' parsed successfully. Please preview and confirm import.");
        } catch (\Throwable $e) {
            session()->flash('error', 'Error reading import file: ' . $e->getMessage());
        }
    }

    public function confirmImport(QuestionImportService $importService): void
    {
        if (!$this->activeBatchId) {
            return;
        }

        $batch = QuestionImportBatch::findOrFail($this->activeBatchId);

        $this->isImporting = true;
        try {
            $result = $importService->executeImport($batch);
            $this->importSummary = $result;
            session()->flash('success', "Import completed! Successfully added {$result['imported']} questions to the Question Bank.");
        } catch (\Throwable $e) {
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        } finally {
            $this->isImporting = false;
        }
    }

    public function cancelPreview(): void
    {
        $this->activeBatchId = null;
        $this->importSummary = null;
        $this->file = null;
    }

    public function render(): View
    {
        $activeBatch = $this->activeBatchId ? QuestionImportBatch::find($this->activeBatchId) : null;
        $previewRows = collect();
        $counts = [
            'all' => 0,
            'valid' => 0,
            'invalid' => 0,
            'duplicate' => 0,
        ];

        if ($activeBatch) {
            $allRows = $activeBatch->rows()->get();
            $counts['all'] = $allRows->count();
            $counts['valid'] = $allRows->where('status', ImportRowStatus::Valid)->count();
            $counts['invalid'] = $allRows->where('status', ImportRowStatus::Invalid)->count();
            $counts['duplicate'] = $allRows->where('status', ImportRowStatus::Duplicate)->count();

            $query = $activeBatch->rows();
            if ($this->rowFilter === 'valid') {
                $query->where('status', ImportRowStatus::Valid->value);
            } elseif ($this->rowFilter === 'invalid') {
                $query->where('status', ImportRowStatus::Invalid->value);
            } elseif ($this->rowFilter === 'duplicate') {
                $query->where('status', ImportRowStatus::Duplicate->value);
            }

            $previewRows = $query->paginate(20);
        }

        return view('livewire.questions.question-import-manager', [
            'activeBatch' => $activeBatch,
            'previewRows' => $previewRows,
            'counts' => $counts,
        ])->layout('layouts.app', ['title' => 'Import Questions', 'header' => 'Excel Question Import Center']);
    }
}
