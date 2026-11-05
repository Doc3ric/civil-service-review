<?php

declare(strict_types=1);

namespace App\Livewire\Examinations;

use App\Models\ExamBlueprint;
use App\Models\User;
use App\Services\Examination\ExamGeneratorService;
use App\Services\Examination\QuestionSelectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FullExamStarter extends Component
{
    public function startExam(
        ExamGeneratorService $generatorService,
        QuestionSelectionService $selectionService
    ): mixed {
        /** @var User $user */
        $user = Auth::user();

        $blueprint = ExamBlueprint::where('is_active', true)
            ->with(['blueprintCategories.category'])
            ->firstOrFail();

        $availability = $selectionService->checkBlueprintAvailability($blueprint);
        if (!$availability['sufficient']) {
            $deficitsList = [];
            foreach ($availability['deficits'] as $catName => $info) {
                $deficitsList[] = "{$catName}: requires {$info['required']} items (only {$info['available']} published)";
            }
            session()->flash('error', 'Cannot start examination. Insufficient published questions: ' . implode('; ', $deficitsList));
            return null;
        }

        $attempt = $generatorService->generateFullExam($user, $blueprint);

        return redirect()->route('exams.runner', ['attempt' => $attempt->id]);
    }

    public function render(QuestionSelectionService $selectionService): View
    {
        $blueprint = ExamBlueprint::where('is_active', true)
            ->with(['blueprintCategories.category'])
            ->firstOrFail();

        $availability = $selectionService->checkBlueprintAvailability($blueprint);

        return view('livewire.examinations.full-exam-starter', [
            'blueprint' => $blueprint,
            'availability' => $availability,
        ])->layout('layouts.app', ['title' => 'Full Practice Examination', 'header' => 'Civil Service Full Practice Examination']);
    }
}
