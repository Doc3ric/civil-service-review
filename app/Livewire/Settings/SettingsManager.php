<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\ExamBlueprint;
use App\Models\ExamBlueprintCategory;
use App\Models\ExamBlueprintCategoryTopic;
use App\Models\ExamCategory;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class SettingsManager extends Component
{
    // Tab
    public string $activeTab = 'exam'; // exam, study, profile

    // Examination Blueprint fields
    public int $blueprintId;
    public string $examName = '';
    public int $totalQuestions = 150;
    public int $durationMinutes = 170;
    public float $passingPercentage = 80.00;
    public array $categoryAllocations = []; // [category_id => count]
    public array $topicAllocations = []; // [topic_id => optional count]

    // Study Settings fields
    public int $dailyQuestionTarget = 20;
    public int $dailyStudyGoalMinutes = 30;
    public string $timezone = 'Asia/Manila';

    // Profile Settings fields
    public string $userName = '';
    public string $userEmail = '';
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        // Blueprint
        $blueprint = ExamBlueprint::where('is_active', true)
            ->with(['blueprintCategories.category', 'blueprintCategories.topicAllocations'])
            ->first();
        if ($blueprint) {
            $this->blueprintId = $blueprint->id;
            $this->examName = $blueprint->name;
            $this->totalQuestions = $blueprint->total_questions;
            $this->durationMinutes = $blueprint->duration_minutes;
            $this->passingPercentage = (float) $blueprint->passing_percentage;

            foreach ($blueprint->blueprintCategories as $bc) {
                $this->categoryAllocations[$bc->category_id] = $bc->question_count;
                foreach ($bc->topicAllocations as $allocation) {
                    $this->topicAllocations[$allocation->topic_id] = $allocation->question_count;
                }
            }
        }

        // Study settings
        $settings = $user->getOrCreateSettings();
        $this->dailyQuestionTarget = $settings->daily_question_target;
        $this->dailyStudyGoalMinutes = $settings->daily_study_goal_minutes;
        $this->timezone = $settings->timezone;

        // Profile
        $this->userName = $user->name;
        $this->userEmail = $user->email;
    }

    public function saveExamSettings(): void
    {
        $rules = [
            'examName' => ['required', 'string', 'max:255'],
            'totalQuestions' => ['required', 'integer', 'min:10', 'max:500'],
            'durationMinutes' => ['required', 'integer', 'min:10', 'max:600'],
            'passingPercentage' => ['required', 'numeric', 'min:1', 'max:100'],
            'categoryAllocations.*' => ['required', 'integer', 'min:0'],
        ];
        foreach ($this->topicAllocations as $topicId => $count) {
            if ($count !== null && trim((string) $count) !== '') {
                $rules["topicAllocations.{$topicId}"] = ['integer', 'min:0', 'max:500'];
            }
        }
        $this->validate($rules);

        $sumAllocations = array_sum($this->categoryAllocations);
        if ($sumAllocations !== $this->totalQuestions) {
            $this->addError('categoryAllocations', "Category counts total {$sumAllocations}, but the exam total is {$this->totalQuestions}.");
            return;
        }

        $categories = ExamCategory::with('topics')->get()->keyBy('id');
        $validCategoryIds = $categories->keys()->map(fn ($id) => (string) $id)->all();
        $providedCategoryIds = array_map('strval', array_keys($this->categoryAllocations));
        if (array_diff($validCategoryIds, $providedCategoryIds) !== [] || array_diff($providedCategoryIds, $validCategoryIds) !== []) {
            $this->addError('categoryAllocations', 'Refresh the page before saving category allocations.');
            return;
        }

        $validTopicIds = $categories->flatMap(fn (ExamCategory $category) => $category->topics->pluck('id'))->map(fn ($id) => (string) $id)->all();
        foreach (array_keys($this->topicAllocations) as $topicId) {
            if (!in_array((string) $topicId, $validTopicIds, true)) {
                $this->addError('topicAllocations', 'Refresh the page before saving topic allocations.');
                return;
            }
        }

        foreach ($categories as $category) {
            $topicIds = $category->topics->pluck('id')->map(fn ($id) => (string) $id)->all();
            $provided = collect($this->topicAllocations)
                ->filter(fn ($count, $topicId) => in_array((string) $topicId, $topicIds, true) && $count !== null && trim((string) $count) !== '');
            if ($provided->isNotEmpty()) {
                $topicTotal = (int) $provided->sum(fn ($count) => (int) $count);
                $categoryTotal = (int) ($this->categoryAllocations[$category->id] ?? 0);
                if ($topicTotal !== $categoryTotal) {
                    $this->addError('topicAllocations', "{$category->name} topic counts total {$topicTotal}, but the category total is {$categoryTotal}.");
                    return;
                }
            }
        }

        DB::transaction(function () use ($categories) {
            $blueprint = ExamBlueprint::findOrFail($this->blueprintId);
            $blueprint->update([
                'name' => $this->examName,
                'total_questions' => $this->totalQuestions,
                'duration_minutes' => $this->durationMinutes,
                'passing_percentage' => $this->passingPercentage,
            ]);

            foreach ($this->categoryAllocations as $catId => $count) {
                $pct = $this->totalQuestions > 0 ? round(($count / $this->totalQuestions) * 100, 2) : 0;
                $blueprintCategory = ExamBlueprintCategory::updateOrCreate(
                    [
                        'exam_blueprint_id' => $blueprint->id,
                        'category_id' => $catId,
                    ],
                    [
                        'question_count' => $count,
                        'percentage' => $pct,
                    ]
                );

                $category = $categories->get($catId);
                $topicIds = $category?->topics->pluck('id')->map(fn ($id) => (string) $id)->all() ?? [];
                $provided = collect($this->topicAllocations)
                    ->filter(fn ($topicCount, $topicId) => in_array((string) $topicId, $topicIds, true) && $topicCount !== null && trim((string) $topicCount) !== '');

                $blueprintCategory->topicAllocations()->delete();
                foreach ($provided as $topicId => $topicCount) {
                    ExamBlueprintCategoryTopic::create([
                        'exam_blueprint_category_id' => $blueprintCategory->id,
                        'topic_id' => (int) $topicId,
                        'question_count' => (int) $topicCount,
                    ]);
                }
            }
        });

        session()->flash('success', 'Examination blueprint settings updated successfully.');
    }

    public function saveStudySettings(): void
    {
        $this->validate([
            'dailyQuestionTarget' => ['required', 'integer', 'min:1', 'max:500'],
            'dailyStudyGoalMinutes' => ['required', 'integer', 'min:5', 'max:600'],
            'timezone' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'daily_question_target' => $this->dailyQuestionTarget,
                'daily_study_goal_minutes' => $this->dailyStudyGoalMinutes,
                'timezone' => $this->timezone,
            ]
        );

        session()->flash('success', 'Study consistency settings updated.');
    }

    public function saveProfileSettings(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->validate([
            'userName' => ['required', 'string', 'max:255'],
            'userEmail' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'currentPassword' => ['nullable', 'string'],
            'newPassword' => ['nullable', 'string', 'min:8'],
            'newPasswordConfirmation' => ['nullable', 'same:newPassword'],
        ]);

        $user->name = $this->userName;
        $user->email = $this->userEmail;

        if (!empty($this->newPassword)) {
            if (empty($this->currentPassword) || !Hash::check($this->currentPassword, $user->password)) {
                $this->addError('currentPassword', 'Current password is incorrect.');
                return;
            }
            $user->password = Hash::make($this->newPassword);
        }

        $user->save();

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';

        session()->flash('success', 'Account profile updated successfully.');
    }

    public function render(): View
    {
        $categories = ExamCategory::with('topics')->orderBy('sort_order')->get();

        return view('livewire.settings.settings-manager', [
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => 'Settings', 'header' => 'System & Review Preferences']);
    }
}
