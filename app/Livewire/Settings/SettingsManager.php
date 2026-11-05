<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\ExamBlueprint;
use App\Models\ExamBlueprintCategory;
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
        $blueprint = ExamBlueprint::where('is_active', true)->with('blueprintCategories.category')->first();
        if ($blueprint) {
            $this->blueprintId = $blueprint->id;
            $this->examName = $blueprint->name;
            $this->totalQuestions = $blueprint->total_questions;
            $this->durationMinutes = $blueprint->duration_minutes;
            $this->passingPercentage = (float) $blueprint->passing_percentage;

            foreach ($blueprint->blueprintCategories as $bc) {
                $this->categoryAllocations[$bc->category_id] = $bc->question_count;
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
        $this->validate([
            'examName' => ['required', 'string', 'max:255'],
            'totalQuestions' => ['required', 'integer', 'min:10', 'max:500'],
            'durationMinutes' => ['required', 'integer', 'min:10', 'max:600'],
            'passingPercentage' => ['required', 'numeric', 'min:1', 'max:100'],
            'categoryAllocations.*' => ['required', 'integer', 'min:0'],
        ]);

        $sumAllocations = array_sum($this->categoryAllocations);
        if ($sumAllocations !== $this->totalQuestions) {
            session()->flash('error', "Category question counts sum to {$sumAllocations}, which does not match total questions ({$this->totalQuestions}).");
            return;
        }

        DB::transaction(function () {
            $blueprint = ExamBlueprint::findOrFail($this->blueprintId);
            $blueprint->update([
                'name' => $this->examName,
                'total_questions' => $this->totalQuestions,
                'duration_minutes' => $this->durationMinutes,
                'passing_percentage' => $this->passingPercentage,
            ]);

            foreach ($this->categoryAllocations as $catId => $count) {
                $pct = $this->totalQuestions > 0 ? round(($count / $this->totalQuestions) * 100, 2) : 0;
                ExamBlueprintCategory::updateOrCreate(
                    [
                        'exam_blueprint_id' => $blueprint->id,
                        'category_id' => $catId,
                    ],
                    [
                        'question_count' => $count,
                        'percentage' => $pct,
                    ]
                );
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
        $categories = ExamCategory::orderBy('sort_order')->get();

        return view('livewire.settings.settings-manager', [
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => 'Settings', 'header' => 'System & Review Preferences']);
    }
}
