<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\ExamStatus;
use App\Models\ExamBlueprint;
use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\User;
use App\Services\Progress\PerformanceAnalyticsService;
use App\Services\Progress\StreakCalculationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardOverview extends Component
{
    /**
     * @return View
     */
    public function render(
        PerformanceAnalyticsService $analyticsService,
        StreakCalculationService $streakService
    ): View {
        /** @var User $user */
        $user = Auth::user();

        $analytics = $analyticsService->getAnalytics($user, $streakService);
        $streaks = $streakService->calculateStreaks($user);
        $blueprint = ExamBlueprint::where('is_active', true)->with('blueprintCategories.category')->first();

        $categories = ExamCategory::withCount([
            'questions as published_questions_count' => function ($query) {
                $query->where('status', 'published');
            }
        ])->orderBy('sort_order')->get();

        $categoryCards = [];
        $totalBlueprintQuestions = $blueprint ? $blueprint->total_questions : 150;

        foreach ($categories as $cat) {
            $catCode = $cat->code instanceof \BackedEnum ? $cat->code->value : (string) $cat->code;
            $bpCategory = $blueprint?->blueprintCategories->firstWhere('category_id', $cat->id);
            $assignedItems = $bpCategory?->question_count ?? 0;
            $blueprintPercentage = $bpCategory?->percentage ?? ($totalBlueprintQuestions > 0 ? round(($assignedItems / $totalBlueprintQuestions) * 100, 1) : 0);

            $categoryCards[] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'code' => $catCode,
                'description' => $cat->description,
                'color' => $cat->color ?? '#2563EB',
                'icon' => $cat->icon ?? 'book-open',
                'available_count' => $cat->published_questions_count,
                'assigned_items' => $assignedItems,
                'blueprint_percentage' => $blueprintPercentage,
                'performance' => $analytics['category_stats'][$catCode]['percentage'] ?? 0.0,
            ];
        }

        $now = Carbon::now($user->settings->timezone ?? 'Asia/Manila');

        $motivationalQuotes = [
            'Preparation today is the confidence of tomorrow. Consistent daily practice secures your civil service eligibility!',
            'Mastery comes from repetition. Keep practicing questions and reviewing the explanations.',
            'Every question reviewed brings you one step closer to topping the Civil Service Exam!',
            'Consistency beats cramming. Build your daily streak one question at a time.',
        ];
        $quoteIndex = (int) $now->dayOfYear % count($motivationalQuotes);
        $dailyQuote = $motivationalQuotes[$quoteIndex];

        return view('livewire.dashboard.dashboard-overview', [
            'user' => $user,
            'currentDate' => $now->format('l, F j, Y'),
            'dailyQuote' => $dailyQuote,
            'analytics' => $analytics,
            'streaks' => $streaks,
            'categoryCards' => $categoryCards,
            'recentAttempts' => $analytics['recent_attempts'],
            'blueprint' => $blueprint,
        ])->layout('layouts.app', ['title' => 'Dashboard', 'header' => 'Dashboard Overview']);
    }
}
