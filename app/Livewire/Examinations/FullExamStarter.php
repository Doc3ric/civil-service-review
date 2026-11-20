<?php

declare(strict_types=1);

namespace App\Livewire\Examinations;

use App\Models\ExamBlueprint;
use App\Models\Question;
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
            ->with(['blueprintCategories.category', 'blueprintCategories.topicAllocations.topic'])
            ->firstOrFail();

        $availability = $selectionService->checkBlueprintAvailability($blueprint);
        if (!$availability['sufficient']) {
            $deficitsList = [];
            foreach ($availability['deficits'] as $catName => $info) {
                $deficitsList[] = $info['reason'] ?? "{$catName}: requires {$info['required']} items (only {$info['available']} published)";
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
            ->with(['blueprintCategories.category', 'blueprintCategories.topicAllocations.topic'])
            ->firstOrFail();

        $availability = $selectionService->checkBlueprintAvailability($blueprint);
        $topicIds = $blueprint->blueprintCategories->flatMap(fn ($category) => $category->topicAllocations->pluck('topic_id'))->unique()->values();
        $topicAvailability = $topicIds->isEmpty()
            ? []
            : Question::published()
                ->whereIn('topic_id', $topicIds)
                ->selectRaw('topic_id, COUNT(*) as available_count')
                ->groupBy('topic_id')
                ->pluck('available_count', 'topic_id')
                ->all();

        return view('livewire.examinations.full-exam-starter', [
            'blueprint' => $blueprint,
            'availability' => $availability,
            'topicAvailability' => $topicAvailability,
        ])->layout('layouts.app', ['title' => 'Full Practice Examination', 'header' => 'Civil Service Full Practice Examination']);
    }
}
