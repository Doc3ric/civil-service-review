<?php

declare(strict_types=1);

namespace App\Livewire\Questions;

use App\Models\QuestionImportBatch;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ImportHistoryList extends Component
{
    use WithPagination;

    public function render(): View
    {
        $batches = QuestionImportBatch::withCount('questions')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.questions.import-history-list', [
            'batches' => $batches,
        ])->layout('layouts.app', ['title' => 'Import History', 'header' => 'Question Import History']);
    }
}
