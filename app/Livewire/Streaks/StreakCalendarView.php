<?php

declare(strict_types=1);

namespace App\Livewire\Streaks;

use App\Models\User;
use App\Services\Progress\StreakCalculationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StreakCalendarView extends Component
{
    public int $year;
    public int $month;

    public function mount(?int $year = null, ?int $month = null): void
    {
        /** @var User $user */
        $user = Auth::user();
        $timezone = $user->settings->timezone ?? 'Asia/Manila';
        $now = Carbon::now($timezone);

        $this->year = $year ?? (int) $now->year;
        $this->month = $month ?? (int) $now->month;
    }

    public function previousMonth(): void
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->year = (int) $date->year;
        $this->month = (int) $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->year = (int) $date->year;
        $this->month = (int) $date->month;
    }

    public function goToCurrentMonth(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $timezone = $user->settings->timezone ?? 'Asia/Manila';
        $now = Carbon::now($timezone);

        $this->year = (int) $now->year;
        $this->month = (int) $now->month;
    }

    public function render(StreakCalculationService $streakService): View
    {
        /** @var User $user */
        $user = Auth::user();

        $streaks = $streakService->calculateStreaks($user);
        $calendar = $streakService->getMonthlyCalendar($user, $this->year, $this->month);

        return view('livewire.streaks.streak-calendar-view', [
            'streaks' => $streaks,
            'calendar' => $calendar,
        ])->layout('layouts.app', ['title' => 'Streak Calendar', 'header' => 'Daily Study Streak & Consistency Calendar']);
    }
}
