<?php

declare(strict_types=1);

namespace App\Livewire\Questions;

use App\Models\ExamBlueprint;
use App\Models\ExamCategory;
use App\Models\QuestionTopic;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CategoryList extends Component
{
    public int $topicCategoryId = 1;
    public string $topicName = '';
    public ?int $editingTopicId = null;

    public function mount(): void
    {
        $this->topicCategoryId = ExamCategory::value('id') ?? 1;
    }

    public function editTopic(int $topicId): void
    {
        $topic = QuestionTopic::findOrFail($topicId);
        $this->editingTopicId = $topic->id;
        $this->topicCategoryId = $topic->category_id;
        $this->topicName = $topic->name;
    }

    public function cancelTopicEdit(): void
    {
        $this->editingTopicId = null;
        $this->topicName = '';
    }

    public function saveTopic(): void
    {
        $this->topicName = mb_strtoupper(trim($this->topicName));
        $this->validate([
            'topicCategoryId' => ['required', 'integer', 'exists:exam_categories,id'],
            'topicName' => [
                'required',
                'string',
                'max:120',
                Rule::unique('question_topics', 'name')
                    ->where('category_id', $this->topicCategoryId)
                    ->ignore($this->editingTopicId),
            ],
        ]);

        $topic = $this->editingTopicId ? QuestionTopic::findOrFail($this->editingTopicId) : new QuestionTopic();
        if ($this->editingTopicId && (int) $this->topicCategoryId !== (int) $topic->category_id) {
            $this->addError('topicCategoryId', 'A topic stays under its original category.');
            return;
        }
        $topic->category_id = $this->topicCategoryId;
        $topic->name = $this->topicName;
        $topic->save();

        $this->cancelTopicEdit();
        session()->flash('success', 'Question topic saved.');
    }

    public function deleteTopic(int $topicId): void
    {
        $topic = QuestionTopic::findOrFail($topicId);
        $topic->delete();
        session()->flash('success', 'Topic deleted. Questions using it are now uncategorized; past exam reviews keep their saved topic names.');
    }

    public function render(): View
    {
        $blueprint = ExamBlueprint::where('is_active', true)
            ->with('blueprintCategories')
            ->first();

        $categories = ExamCategory::with(['topics' => fn ($query) => $query->withCount('questions')])->withCount([
            'questions as published_count' => fn($q) => $q->where('status', 'published'),
            'questions as draft_count' => fn($q) => $q->where('status', 'draft'),
            'questions as archived_count' => fn($q) => $q->where('status', 'archived'),
        ])->orderBy('sort_order')->get();

        return view('livewire.questions.category-list', [
            'categories' => $categories,
            'blueprint' => $blueprint,
        ])->layout('layouts.app', ['title' => 'Categories', 'header' => 'Examination Categories']);
    }
}
