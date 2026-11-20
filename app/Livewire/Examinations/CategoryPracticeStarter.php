<?php

declare(strict_types=1);

namespace App\Livewire\Examinations;

use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\User;
use App\Services\Examination\ExamGeneratorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CategoryPracticeStarter extends Component
{
    public int $selectedCategoryId = 1;
    public int $selectedCount = 20;
    public string $selectedTopicId = '';

    public function updatedSelectedCategoryId(): void
    {
        $this->selectedTopicId = '';
    }

    public function mount(?int $category_id = null, ?int $topic_id = null): void
    {
        if ($category_id !== null) {
            $cat = ExamCategory::find($category_id);
            if ($cat) {
                $this->selectedCategoryId = $cat->id;
            }
        }

        if ($topic_id !== null && QuestionTopic::where('category_id', $this->selectedCategoryId)->whereKey($topic_id)->exists()) {
            $this->selectedTopicId = (string) $topic_id;
        }
    }

    public function startPractice(ExamGeneratorService $generatorService): mixed
    {
        /** @var User $user */
        $user = Auth::user();

        $category = ExamCategory::findOrFail($this->selectedCategoryId);

        $topic = null;
        if ($this->selectedTopicId !== '') {
            $topic = QuestionTopic::where('category_id', $category->id)->findOrFail((int) $this->selectedTopicId);
        }

        $available = Question::published()
            ->where('category_id', $category->id)
            ->when($topic, fn ($query) => $query->where('topic_id', $topic->id))
            ->count();
        if ($available === 0) {
            $focus = $topic?->name ?? $category->name;
            session()->flash('error', "No published questions found for {$focus}.");
            return null;
        }

        $count = $this->selectedCount === 9999 ? $available : min($this->selectedCount, $available);

        $attempt = $topic
            ? $generatorService->generateTopicPractice($user, $topic, $count)
            : $generatorService->generateCategoryPractice($user, $category, $count);

        return redirect()->route('exams.runner', ['attempt' => $attempt->id]);
    }

    public function render(): View
    {
        $categories = ExamCategory::with(['topics' => fn ($query) => $query->withCount([
            'questions as published_count' => fn ($questions) => $questions->where('status', 'published'),
        ])])->withCount([
            'questions as published_count' => fn($q) => $q->where('status', 'published'),
        ])->orderBy('sort_order')->get();

        $currentCat = $categories->firstWhere('id', $this->selectedCategoryId) ?? $categories->first();
        $selectedTopic = $this->selectedTopicId !== ''
            ? $currentCat?->topics->firstWhere('id', (int) $this->selectedTopicId)
            : null;

        return view('livewire.examinations.category-practice-starter', [
            'categories' => $categories,
            'currentCat' => $currentCat,
            'currentTopics' => $currentCat?->topics ?? collect(),
            'selectedTopic' => $selectedTopic,
        ])->layout('layouts.app', ['title' => 'Category Practice', 'header' => 'Subject Category Practice']);
    }
}
