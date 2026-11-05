<?php

declare(strict_types=1);

namespace App\Livewire\Results;

use App\Models\ExamAttempt;
use App\Services\Examination\ExamReviewService;
use App\Services\Examination\ExamScoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ExamResultViewer extends Component
{
    public int $attemptId;
    public string $reviewFilter = 'all'; // all, incorrect, correct, unanswered

    public function mount(int $attempt): void
    {
        $this->attemptId = $attempt;

        $examAttempt = ExamAttempt::findOrFail($attempt);
        if ($examAttempt->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this examination result.');
        }
    }

    public function render(ExamScoringService $scoringService, ExamReviewService $reviewService): View
    {
        $attempt = ExamAttempt::with(['attemptQuestions.answer', 'category', 'blueprint'])->findOrFail($this->attemptId);

        $scoreData = $scoringService->calculateScore($attempt);
        $reviewItems = $reviewService->getReviewItems($attempt);

        // Filter items
        $filteredItems = array_filter($reviewItems, function ($item): bool {
            if ($this->reviewFilter === 'incorrect') {
                return !$item['is_correct'] && !$item['is_unanswered'];
            }
            if ($this->reviewFilter === 'correct') {
                return $item['is_correct'];
            }
            if ($this->reviewFilter === 'unanswered') {
                return $item['is_unanswered'];
            }
            return true;
        });

        return view('livewire.results.exam-result-viewer', [
            'attempt' => $attempt,
            'scoreData' => $scoreData,
            'reviewItems' => $filteredItems,
            'totalItemsCount' => count($reviewItems),
        ])->layout('layouts.app', ['title' => 'Examination Result', 'header' => 'Examination Scoring & Item Review']);
    }
}
