<?php

declare(strict_types=1);

namespace App\Livewire\Analytics;

use App\Models\User;
use App\Services\Progress\PerformanceAnalyticsService;
use App\Services\Progress\StreakCalculationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PerformanceAnalyticsDashboard extends Component
{
    public function render(
        PerformanceAnalyticsService $analyticsService,
        StreakCalculationService $streakService
    ): View {
        /** @var User $user */
        $user = Auth::user();

        $analytics = $analyticsService->getAnalytics($user, $streakService);

        return view('livewire.analytics.performance-analytics-dashboard', [
            'analytics' => $analytics,
        ])->layout('layouts.app', ['title' => 'Performance Analytics', 'header' => 'Performance & Readiness Analytics']);
    }
}
