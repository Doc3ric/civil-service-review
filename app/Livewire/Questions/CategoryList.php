<?php

declare(strict_types=1);

namespace App\Livewire\Questions;

use App\Models\ExamBlueprint;
use App\Models\ExamCategory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CategoryList extends Component
{
    public function render(): View
    {
        $blueprint = ExamBlueprint::where('is_active', true)
            ->with('blueprintCategories')
            ->first();

        $categories = ExamCategory::withCount([
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
