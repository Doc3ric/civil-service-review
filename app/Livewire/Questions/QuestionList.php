<?php

declare(strict_types=1);

namespace App\Livewire\Questions;

use App\Enums\CategoryCode;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionOrigin;
use App\Enums\QuestionStatus;
use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Services\QuestionBank\QuestionManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class QuestionList extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public string $categoryId = '';
    public string $topicId = '';
    public string $difficulty = '';
    public string $status = '';
    public string $sortBy = 'created_at';
    public string $sortDir = 'desc';

    // Modal state for View Question
    public ?Question $viewingQuestion = null;
    public bool $showViewModal = false;

    // Modal state for Delete All confirmation
    public bool $showDeleteAllModal = false;
    public bool $showDeleteSampleModal = false;

    // Modal state for Create / Edit Question
    public bool $showEditModal = false;
    public ?int $editingQuestionId = null;
    public int $formCategoryId = 1;
    public string $formTopicId = '';
    public string $formQuestionText = '';
    public string $formChoiceA = '';
    public string $formChoiceB = '';
    public string $formChoiceC = '';
    public string $formChoiceD = '';
    public string $formCorrectChoice = 'A';
    public string $formExplanation = '';
    public $formExplanationImage = null;
    public ?string $existingExplanationImage = null;
    public bool $removeExplanationImage = false;
    public string $formDifficulty = 'medium';
    public string $formSource = '';
    public string $formStatus = 'published';

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['except' => ''],
        'topicId' => ['except' => ''],
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
        $this->topicId = '';
    }

    public function updatingDifficulty(): void
    {
        $this->resetPage();
    }

    public function updatingTopicId(): void
    {
        $this->resetPage();
    }

    public function updatedFormCategoryId(): void
    {
        $this->formTopicId = '';
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function openViewModal(int $id): void
    {
        $this->viewingQuestion = Question::with(['category', 'topic', 'choices', 'importBatch'])->findOrFail($id);
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
        $this->resetValidation();
        $question = Question::with('choices')->findOrFail($id);
        $this->editingQuestionId = $question->id;
        $this->formCategoryId = (int) $question->category_id;
        $this->formTopicId = $question->topic_id ? (string) $question->topic_id : '';
        $this->formQuestionText = (string) $question->question_text;
        $this->formCorrectChoice = strtoupper(trim((string) $question->correct_choice));
        $this->formExplanation = (string) ($question->explanation ?? '');
        $this->formDifficulty = $question->difficulty instanceof \BackedEnum ? $question->difficulty->value : (string) $question->difficulty;
        $this->formSource = (string) ($question->source ?? '');
        $this->formStatus = $question->status instanceof \BackedEnum ? $question->status->value : (string) $question->status;

        $choicesMap = $question->choices->keyBy(fn ($c) => strtoupper(trim((string) $c->choice_label)));
        $this->formChoiceA = (string) ($choicesMap->get('A')?->choice_text ?? '');
        $this->formChoiceB = (string) ($choicesMap->get('B')?->choice_text ?? '');
        $this->formChoiceC = (string) ($choicesMap->get('C')?->choice_text ?? '');
        $this->formChoiceD = (string) ($choicesMap->get('D')?->choice_text ?? '');
        $this->formExplanationImage = null;
        $this->existingExplanationImage = $question->explanation_image;
        $this->removeExplanationImage = false;

        $this->showEditModal = true;
    }

    public function clearExplanationImage(): void
    {
        $this->formExplanationImage = null;
        $this->existingExplanationImage = null;
        $this->removeExplanationImage = true;
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
            'formTopicId' => $this->formTopicId === ''
                ? ['nullable']
                : [
                    'required',
                    'integer',
                    \Illuminate\Validation\Rule::exists('question_topics', 'id')->where('category_id', $this->formCategoryId),
                ],
            'formQuestionText' => ['required', 'string', 'min:5'],
            'formChoiceA' => ['required'],
            'formChoiceB' => ['required'],
            'formChoiceC' => ['required'],
            'formChoiceD' => ['required'],
            'formCorrectChoice' => ['required', 'in:A,B,C,D,a,b,c,d'],
            'formDifficulty' => ['required', 'in:easy,medium,hard,Easy,Medium,Hard'],
            'formStatus' => ['required', 'in:draft,published,archived,Draft,Published,Archived'],
            'formExplanation' => ['nullable', 'string'],
            'formExplanationImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'formSource' => ['nullable', 'string', 'max:255'],
        ]);

        $explanationImagePath = $this->existingExplanationImage;

        if ($this->removeExplanationImage) {
            if ($this->existingExplanationImage && Storage::disk('public')->exists($this->existingExplanationImage)) {
                Storage::disk('public')->delete($this->existingExplanationImage);
            }
            $explanationImagePath = null;
        }

        if ($this->formExplanationImage) {
            if ($this->existingExplanationImage && Storage::disk('public')->exists($this->existingExplanationImage)) {
                Storage::disk('public')->delete($this->existingExplanationImage);
            }

            if (!Storage::disk('public')->exists('explanations')) {
                Storage::disk('public')->makeDirectory('explanations');
            }

            $explanationImagePath = $this->formExplanationImage->store('explanations', 'public');
        }

        $payload = [
            'category_id' => (int) $this->formCategoryId,
            'topic_id' => $this->formTopicId !== '' ? (int) $this->formTopicId : null,
            'question_text' => trim($this->formQuestionText),
            'choices' => [
                'A' => trim((string) $this->formChoiceA),
                'B' => trim((string) $this->formChoiceB),
                'C' => trim((string) $this->formChoiceC),
                'D' => trim((string) $this->formChoiceD),
            ],
            'correct_choice' => strtoupper(trim($this->formCorrectChoice)),
            'explanation' => trim($this->formExplanation) !== '' ? trim($this->formExplanation) : null,
            'explanation_image' => $explanationImagePath,
            'difficulty' => strtolower(trim($this->formDifficulty)),
            'source' => trim($this->formSource) !== '' ? trim($this->formSource) : null,
            'status' => strtolower(trim($this->formStatus)),
        ];

        if ($this->editingQuestionId) {
            $question = Question::findOrFail($this->editingQuestionId);
            $service->updateQuestion($question, $payload);
            session()->flash('success', "Question #{$this->editingQuestionId} updated successfully.");
        } else {
            $question = $service->createQuestion($payload);
            session()->flash('success', "Question #{$question->id} created successfully.");
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

    public function confirmDeleteSampleQuestions(): void
    {
        $this->showDeleteSampleModal = true;
    }

    public function cancelDeleteSampleQuestions(): void
    {
        $this->showDeleteSampleModal = false;
    }

    public function deleteSampleQuestions(QuestionManagementService $service): void
    {
        $count = $service->deleteSampleQuestions();
        $this->showDeleteSampleModal = false;
        $this->resetPage();
        session()->flash('success', "Removed {$count} built-in sample questions. Your manual and imported questions were kept.");
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
        $this->formTopicId = '';
        $this->formExplanation = '';
        $this->formExplanationImage = null;
        $this->existingExplanationImage = null;
        $this->removeExplanationImage = false;
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
            'topic_id' => $this->topicId ? (int) $this->topicId : null,
            'difficulty' => $this->difficulty,
            'status' => $this->status,
            'sort_by' => $this->sortBy,
            'sort_dir' => $this->sortDir,
            'per_page' => 15,
        ];

        $questions = $service->getPaginatedQuestions($filters);
        $categories = ExamCategory::orderBy('sort_order')->get();
        $topics = QuestionTopic::query()
            ->when($this->categoryId !== '', fn ($query) => $query->where('category_id', (int) $this->categoryId))
            ->orderBy('name')
            ->get();
        $formTopics = QuestionTopic::where('category_id', $this->formCategoryId)->orderBy('name')->get();
        $sampleQuestionCount = Question::withTrashed()->where('origin', QuestionOrigin::Sample->value)->count();
        $activeSampleQuestionCount = Question::where('origin', QuestionOrigin::Sample->value)->count();

        return view('livewire.questions.question-list', [
            'questions' => $questions,
            'categories' => $categories,
            'topics' => $topics,
            'formTopics' => $formTopics,
            'sampleQuestionCount' => $sampleQuestionCount,
            'activeSampleQuestionCount' => $activeSampleQuestionCount,
        ])->layout('layouts.app', ['title' => 'Question Bank', 'header' => 'Question Bank Management']);
    }
}
