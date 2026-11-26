<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\QuestionStatus;
use App\Enums\QuestionOrigin;
use App\Http\Controllers\Controller;
use App\Models\ExamBlueprint;
use App\Models\ExamBlueprintCategory;
use App\Models\ExamCategory;
use App\Models\MobileAccessToken;
use App\Models\Question;
use App\Models\User;
use App\Services\Progress\MobileProgressSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileQuestionBankController extends Controller
{
    public function __invoke(Request $request, MobileProgressSnapshotService $progressSnapshotService): JsonResponse
    {
        $accessToken = $request->attributes->get('mobile_access_token');
        $user = $accessToken instanceof MobileAccessToken ? $accessToken->user : null;

        if (!$user instanceof User) {
            abort(401, 'The mobile session is no longer valid. Sign in again to sync progress.');
        }

        $questions = Question::query()
            ->with(['category:id,code,name', 'topic:id,name', 'choices:id,question_id,choice_label,choice_text'])
            ->where('status', QuestionStatus::Published->value)
            ->where('origin', '!=', QuestionOrigin::Sample->value)
            ->orderBy('id')
            ->get();

        $activeBlueprint = ExamBlueprint::query()
            ->where('is_active', true)
            ->with(['blueprintCategories.category', 'blueprintCategories.topicAllocations.topic'])
            ->orderByDesc('id')
            ->first();

        $questionCounts = $questions->groupBy(fn (Question $question): string => $question->category->code->value);
        $topicCounts = $questions
            ->filter(fn (Question $question): bool => $question->topic !== null)
            ->groupBy(fn (Question $question): string => $question->category->code->value.'|'.$question->topic_id);

        $categories = ExamCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ExamCategory $category): array => [
                'code' => $category->code->value,
                'name' => $category->name,
                'available_questions' => $questionCounts->get($category->code->value, collect())->count(),
                'topics' => $category->topics->map(fn ($topic): array => [
                    'id' => $topic->id,
                    'name' => $topic->name,
                    'available_questions' => $topicCounts->get($category->code->value.'|'.$topic->id, collect())->count(),
                ])->values(),
            ])->values();

        $blueprintCategories = $activeBlueprint?->blueprintCategories->map(fn (ExamBlueprintCategory $allocation): array => [
            'code' => $allocation->category->code->value,
            'name' => $allocation->category->name,
            'question_count' => $allocation->question_count,
            'topics' => $allocation->topicAllocations->map(fn ($topicAllocation): array => [
                'id' => $topicAllocation->topic_id,
                'name' => $topicAllocation->topic->name,
                'question_count' => $topicAllocation->question_count,
            ])->values(),
        ])->values() ?? collect();

        $revision = hash('sha256', implode('|', [
            (string) $questions->max('updated_at'),
            (string) $activeBlueprint?->updated_at,
            (string) DB::table('exam_blueprint_categories')->max('updated_at'),
            (string) DB::table('exam_blueprint_category_topics')->max('updated_at'),
            (string) ExamCategory::query()->max('updated_at'),
            (string) DB::table('question_topics')->max('updated_at'),
        ]));

        return response()->json([
            'revision' => $revision,
            'synced_at' => now()->toIso8601String(),
            'blueprint' => [
                'title' => $activeBlueprint?->name ?? 'Civil Service Professional Level',
                'total_questions' => $activeBlueprint?->total_questions ?? 150,
                'duration_minutes' => $activeBlueprint?->duration_minutes ?? 170,
                'passing_percentage' => (float) ($activeBlueprint?->passing_percentage ?? 80),
                'categories' => $blueprintCategories,
            ],
            'categories' => $categories,
            'progress' => $progressSnapshotService->forUser($user),
            'questions' => $questions->map(fn (Question $question): array => [
                'id' => $question->id,
                'category' => $question->category->code->value,
                'topic' => $question->topic?->name ?? '',
                'prompt' => $question->question_text,
                'choices' => $question->choices->map(fn ($choice): array => [
                    'label' => $choice->choice_label,
                    'text' => $choice->choice_text,
                ])->values(),
                'correct_answer' => $question->correct_choice,
                'explanation' => $question->explanation ?? '',
                'difficulty' => $question->difficulty->value,
                'source' => $question->source ?? '',
            ])->values(),
        ]);
    }
}
