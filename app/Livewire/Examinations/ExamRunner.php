<?php

declare(strict_types=1);

namespace App\Livewire\Examinations;

use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Services\Examination\ExamSubmissionService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ExamRunner extends Component
{
    public int $attemptId;
    public int $currentQuestionNumber = 1;
    public bool $showSubmitModal = false;

    // Cache of selected answers: [question_number => 'A'|'B'|'C'|'D'|null]
    public array $userAnswers = [];

    public function mount(int $attempt): mixed
    {
        $this->attemptId = $attempt;

        $examAttempt = ExamAttempt::with(['attemptQuestions.answer'])->findOrFail($attempt);

        // Security & authorization check
        $this->authorizeAttempt($examAttempt);

        // If completed or expired, redirect to result
        if ($examAttempt->isCompleted()) {
            return redirect()->route('results.show', ['attempt' => $examAttempt->id]);
        }

        // Populate answer state for recovery on browser refresh
        foreach ($examAttempt->attemptQuestions as $aq) {
            $this->userAnswers[$aq->question_number] = $aq->answer?->selected_choice_label;
        }

        return null;
    }

    public function selectAnswer(string $choiceLabel, ExamSubmissionService $submissionService): void
    {
        $attempt = ExamAttempt::findOrFail($this->attemptId);
        $this->authorizeAttempt($attempt);

        $choice = strtoupper(trim($choiceLabel));
        if (!in_array($choice, ['A', 'B', 'C', 'D'], true)) {
            return;
        }

        if ($attempt->isExpired()) {
            $submissionService->submitExam($attempt, isExpired: true);
            $this->redirect(route('results.show', ['attempt' => $attempt->id]));
            return;
        }

        $this->userAnswers[$this->currentQuestionNumber] = $choice;
        $submissionService->saveAnswer($attempt, $this->currentQuestionNumber, $choice);
    }

    public function goToQuestion(int $number): void
    {
        $attempt = ExamAttempt::findOrFail($this->attemptId);
        $this->authorizeAttempt($attempt);

        $total = $attempt->total_questions;

        if ($number >= 1 && $number <= $total) {
            $this->currentQuestionNumber = $number;
        }
    }

    public function nextQuestion(): void
    {
        $this->goToQuestion($this->currentQuestionNumber + 1);
    }

    public function previousQuestion(): void
    {
        $this->goToQuestion($this->currentQuestionNumber - 1);
    }

    public function openSubmitModal(): void
    {
        $this->showSubmitModal = true;
    }

    public function closeSubmitModal(): void
    {
        $this->showSubmitModal = false;
    }

    public function submitExam(ExamSubmissionService $submissionService): mixed
    {
        $attempt = ExamAttempt::findOrFail($this->attemptId);
        $this->authorizeAttempt($attempt);

        $submissionService->submitExam($attempt);

        return redirect()->route('results.show', ['attempt' => $attempt->id]);
    }

    public function handleTimeExpired(ExamSubmissionService $submissionService): mixed
    {
        $attempt = ExamAttempt::findOrFail($this->attemptId);
        $this->authorizeAttempt($attempt);

        $submissionService->submitExam($attempt, isExpired: true);

        return redirect()->route('results.show', ['attempt' => $attempt->id]);
    }

    public function render(): View
    {
        $attempt = ExamAttempt::with(['attemptQuestions.category'])->findOrFail($this->attemptId);
        $this->authorizeAttempt($attempt);

        /** @var ExamAttemptQuestion $currentQuestion */
        $currentQuestion = $attempt->attemptQuestions
            ->firstWhere('question_number', $this->currentQuestionNumber)
            ?? $attempt->attemptQuestions->first();

        $totalQuestions = $attempt->total_questions;
        $answeredCount = count(array_filter($this->userAnswers));
        $unansweredCount = $totalQuestions - $answeredCount;
        $remainingSeconds = $attempt->remainingSeconds();

        return view('livewire.examinations.exam-runner', [
            'attempt' => $attempt,
            'currentQuestion' => $currentQuestion,
            'totalQuestions' => $totalQuestions,
            'answeredCount' => $answeredCount,
            'unansweredCount' => $unansweredCount,
            'remainingSeconds' => $remainingSeconds,
            'currentAnswer' => $this->userAnswers[$this->currentQuestionNumber] ?? null,
        ])->layout('layouts.app', ['title' => $attempt->title, 'header' => $attempt->title]);
    }

    private function authorizeAttempt(ExamAttempt $attempt): void
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this examination.');
        }
    }
}
