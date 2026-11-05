<?php

declare(strict_types=1);

namespace App\Livewire\Questions;

use App\Enums\CategoryCode;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Models\ExamCategory;
use App\Models\Question;
use App\Services\QuestionBank\QuestionManagementService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class QuestionList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryId = '';
    public string $difficulty = '';
    public string $status = '';
    public string $sortBy = 'created_at';
    public string $sortDir = 'desc';

    // Modal state for View Question
    public ?Question $viewingQuestion = null;
    public bool $showViewModal = false;

    // Modal state for Delete All confirmation
    public bool $showDeleteAllModal = false;

    // Modal state for Create / Edit Question
    public bool $showEditModal = false;
    public ?int $editingQuestionId = null;
    public int $formCategoryId = 1;
    public string $formQuestionText = '';
    public string $formChoiceA = '';
    public string $formChoiceB = '';
    public string $formChoiceC = '';
    public string $formChoiceD = '';
    public string $formCorrectChoice = 'A';
    public string $formExplanation = '';
    public string $formDifficulty = 'medium';
    public string $formSource = '';
    public string $formStatus = 'published';

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['except' => ''],
        'difficulty' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatingDifficulty(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function openViewModal(int $id): void
    {
        $this->viewingQuestion = Question::with(['category', 'choices', 'importBatch'])->findOrFail($id);
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->viewingQuestion = null;
        $this->showViewModal = false;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $firstCategory = ExamCategory::first();
        if ($firstCategory) {
            $this->formCategoryId = $firstCategory->id;
        }
        $this->editingQuestionId = null;
        $this->showEditModal = true;
    }

    public function openEditModal(int $id): void
    {
        $question = Question::with('choices')->findOrFail($id);
        $this->editingQuestionId = $question->id;
        $this->formCategoryId = $question->category_id;
        $this->formQuestionText = $question->question_text;
        $this->formCorrectChoice = $question->correct_choice;
        $this->formExplanation = $question->explanation ?? '';
        $this->formDifficulty = $question->difficulty instanceof \BackedEnum ? $question->difficulty->value : (string) $question->difficulty;
        $this->formSource = $question->source ?? '';
        $this->formStatus = $question->status instanceof \BackedEnum ? $question->status->value : (string) $question->status;

        $choicesMap = $question->choices->keyBy('choice_label');
        $this->formChoiceA = $choicesMap['A']->choice_text ?? '';
        $this->formChoiceB = $choicesMap['B']->choice_text ?? '';
        $this->formChoiceC = $choicesMap['C']->choice_text ?? '';
        $this->formChoiceD = $choicesMap['D']->choice_text ?? '';

        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function saveQuestion(QuestionManagementService $service): void
    {
        $this->validate([
            'formCategoryId' => ['required', 'exists:exam_categories,id'],
            'formQuestionText' => ['required', 'string', 'min:5'],
            'formChoiceA' => ['required', 'string'],
            'formChoiceB' => ['required', 'string'],
            'formChoiceC' => ['required', 'string'],
            'formChoiceD' => ['required', 'string'],
            'formCorrectChoice' => ['required', 'in:A,B,C,D'],
            'formDifficulty' => ['required', 'in:easy,medium,hard'],
            'formStatus' => ['required', 'in:draft,published,archived'],
            'formExplanation' => ['nullable', 'string'],
            'formSource' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'category_id' => (int) $this->formCategoryId,
            'question_text' => $this->formQuestionText,
            'choices' => [
                'A' => $this->formChoiceA,
                'B' => $this->formChoiceB,
                'C' => $this->formChoiceC,
                'D' => $this->formChoiceD,
            ],
            'correct_choice' => $this->formCorrectChoice,
            'explanation' => $this->formExplanation ?: null,
            'difficulty' => $this->formDifficulty,
            'source' => $this->formSource ?: null,
            'status' => $this->formStatus,
        ];

        if ($this->editingQuestionId) {
            $question = Question::findOrFail($this->editingQuestionId);
            $service->updateQuestion($question, $payload);
            session()->flash('success', 'Question updated successfully.');
        } else {
            $service->createQuestion($payload);
            session()->flash('success', 'Question created successfully.');
        }

        $this->closeEditModal();
    }

    public function toggleArchive(int $id, QuestionManagementService $service): void
    {
        $question = Question::findOrFail($id);
        if ($question->status === QuestionStatus::Archived) {
            $service->restoreQuestion($question);
            session()->flash('success', 'Question restored to Published status.');
        } else {
            $service->archiveQuestion($question);
            session()->flash('success', 'Question moved to Archived status.');
        }
    }

    public function deleteQuestion(int $id, QuestionManagementService $service): void
    {
        $question = Question::findOrFail($id);
        $service->deleteQuestion($question);
        session()->flash('success', 'Question deleted successfully.');
    }

    public function confirmDeleteAll(): void
    {
        $this->showDeleteAllModal = true;
    }

    public function cancelDeleteAll(): void
    {
        $this->showDeleteAllModal = false;
    }

    public function deleteAllQuestions(QuestionManagementService $service): void
    {
        $count = $service->deleteAllQuestions();
        $this->showDeleteAllModal = false;
        $this->resetPage();
        session()->flash('success', "All {$count} questions have been permanently deleted from the Question Bank.");
    }

    private function resetForm(): void
    {
        $this->editingQuestionId = null;
        $this->formQuestionText = '';
        $this->formChoiceA = '';
        $this->formChoiceB = '';
        $this->formChoiceC = '';
        $this->formChoiceD = '';
        $this->formCorrectChoice = 'A';
        $this->formExplanation = '';
        $this->formDifficulty = 'medium';
        $this->formSource = '';
        $this->formStatus = 'published';
        $this->resetValidation();
    }

    public function render(QuestionManagementService $service): View
    {
        $filters = [
            'search' => $this->search,
            'category_id' => $this->categoryId ? (int) $this->categoryId : null,
            'difficulty' => $this->difficulty,
            'status' => $this->status,
            'sort_by' => $this->sortBy,
            'sort_dir' => $this->sortDir,
            'per_page' => 15,
        ];

        $questions = $service->getPaginatedQuestions($filters);
        $categories = ExamCategory::orderBy('sort_order')->get();

        return view('livewire.questions.question-list', [
            'questions' => $questions,
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => 'Question Bank', 'header' => 'Question Bank Management']);
    }
}
