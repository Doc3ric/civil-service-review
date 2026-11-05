<?php

declare(strict_types=1);

namespace App\Livewire\Examinations;

use App\Enums\ExamStatus;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ExamHistoryList extends Component
{
    use WithPagination;

    public string $modeFilter = '';

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $query = ExamAttempt::where('user_id', $user->id)
            ->whereIn('status', [ExamStatus::Completed->value, ExamStatus::Expired->value])
            ->orderBy('submitted_at', 'desc');

        if ($this->modeFilter !== '') {
            $query->where('mode', $this->modeFilter);
        }

        $attempts = $query->paginate(15);

        return view('livewire.examinations.exam-history-list', [
            'attempts' => $attempts,
        ])->layout('layouts.app', ['title' => 'Examination History', 'header' => 'Examination History']);
    }
}
