<?php

declare(strict_types=1);

namespace App\Livewire\Examinations;

use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\User;
use App\Services\Examination\ExamGeneratorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CategoryPracticeStarter extends Component
{
    public int $selectedCategoryId = 1;
    public int $selectedCount = 20;

    public function mount(?int $category_id = null): void
    {
        if ($category_id !== null) {
            $cat = ExamCategory::find($category_id);
            if ($cat) {
                $this->selectedCategoryId = $cat->id;
            }
        }
    }

    public function startPractice(ExamGeneratorService $generatorService): mixed
    {
        /** @var User $user */
        $user = Auth::user();

        $category = ExamCategory::findOrFail($this->selectedCategoryId);

        $available = Question::published()->where('category_id', $category->id)->count();
        if ($available === 0) {
            session()->flash('error', "No published questions found for {$category->name}.");
            return null;
        }

        $count = $this->selectedCount === 9999 ? $available : min($this->selectedCount, $available);

        $attempt = $generatorService->generateCategoryPractice($user, $category, $count);

        return redirect()->route('exams.runner', ['attempt' => $attempt->id]);
    }

    public function render(): View
    {
        $categories = ExamCategory::withCount([
            'questions as published_count' => fn($q) => $q->where('status', 'published'),
        ])->orderBy('sort_order')->get();

        $currentCat = $categories->firstWhere('id', $this->selectedCategoryId) ?? $categories->first();

        return view('livewire.examinations.category-practice-starter', [
            'categories' => $categories,
            'currentCat' => $currentCat,
        ])->layout('layouts.app', ['title' => 'Category Practice', 'header' => 'Subject Category Practice']);
    }
}
