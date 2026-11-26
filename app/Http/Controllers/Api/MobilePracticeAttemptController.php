<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Models\MobileAccessToken;
use App\Models\MobilePracticeAttempt;
use App\Models\Question;
use App\Models\StudyActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MobilePracticeAttemptController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attempts' => ['present', 'array', 'max:50'],
            'attempts.*.client_attempt_id' => ['required', 'uuid'],
            'attempts.*.title' => ['required', 'string', 'max:120'],
            'attempts.*.mode' => ['required', 'in:category,full'],
            'attempts.*.passing_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'attempts.*.duration_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'attempts.*.completed_at' => ['required', 'date'],
            'attempts.*.answers' => ['required', 'array', 'min:1', 'max:250'],
            'attempts.*.answers.*.question_id' => ['required', 'integer'],
            'attempts.*.answers.*.selected_answer' => ['nullable', 'in:A,B,C,D'],
            'attempts.*.answers.*.flagged' => ['nullable', 'boolean'],
        ]);

        $accessToken = $request->attributes->get('mobile_access_token');
        $user = $accessToken instanceof MobileAccessToken ? $accessToken->user : null;

        if (!$user instanceof User) {
            abort(401, 'The mobile session is no longer valid. Sign in again to sync progress.');
        }

        $acceptedAttemptIds = [];
        $rejectedAttempts = [];

        foreach ($validated['attempts'] as $attemptData) {
            $result = $this->storeAttempt($user, $attemptData);

            if ($result['accepted']) {
                $acceptedAttemptIds[] = $attemptData['client_attempt_id'];
            } else {
                $rejectedAttempts[] = [
                    'client_attempt_id' => $attemptData['client_attempt_id'],
                    'message' => $result['message'],
                ];
            }
        }

        return response()->json([
            'accepted_client_attempt_ids' => $acceptedAttemptIds,
            'rejected' => $rejectedAttempts,
        ]);
    }

    /**
     * @param array{
     *     client_attempt_id: string,
     *     title: string,
     *     mode: string,
     *     passing_percentage: int|float|string,
     *     duration_seconds: int|string,
     *     completed_at: string,
     *     answers: array<int, array{question_id: int|string, selected_answer?: ?string, flagged?: bool}>
     * } $attemptData
     * @return array{accepted: bool, message: string}
     */
    private function storeAttempt(User $user, array $attemptData): array
    {
        return DB::transaction(function () use ($user, $attemptData): array {
            $existingAttempt = MobilePracticeAttempt::query()
                ->where('user_id', $user->id)
                ->where('client_attempt_id', $attemptData['client_attempt_id'])
                ->exists();

            if ($existingAttempt) {
                return ['accepted' => true, 'message' => ''];
            }

            $questionIds = collect($attemptData['answers'])->pluck('question_id')->map(fn ($id): int => (int) $id);
            if ($questionIds->count() !== $questionIds->unique()->count()) {
                return [
                    'accepted' => false,
                    'message' => 'This attempt contains the same question more than once. It remains saved on the phone.',
                ];
            }

            $questions = Question::withTrashed()
                ->with(['category', 'topic', 'choices'])
                ->whereIn('id', $questionIds)
                ->get()
                ->keyBy('id');

            if ($questions->count() !== $questionIds->unique()->count()) {
                return [
                    'accepted' => false,
                    'message' => 'One or more questions no longer exist in the web question bank. This attempt remains saved on the phone.',
                ];
            }

            $categoryScores = [];
            $topicScores = [];
            $answerSnapshots = [];
            $correctCount = 0;

            foreach ($attemptData['answers'] as $answer) {
                $question = $questions->get((int) $answer['question_id']);
                $categoryCode = $question->category->code->value;
                $selectedAnswer = $answer['selected_answer'] ?? null;
                $isCorrect = $selectedAnswer !== null && $selectedAnswer === $question->correct_choice;
                $categoryName = $question->category->name;
                $topicName = $question->topic?->name;

                if ($isCorrect) {
                    $correctCount++;
                }

                $categoryScores[$categoryCode] ??= [
                    'name' => $categoryName,
                    'total' => 0,
                    'correct' => 0,
                ];
                $categoryScores[$categoryCode]['total']++;
                $categoryScores[$categoryCode]['correct'] += (int) $isCorrect;

                if ($topicName !== null) {
                    $topicKey = $categoryCode.'|'.$question->topic_id;
                    $topicScores[$topicKey] ??= [
                        'topic_id' => $question->topic_id,
                        'category_id' => $question->category_id,
                        'category_code' => $categoryCode,
                        'category_name' => $categoryName,
                        'name' => $topicName,
                        'total' => 0,
                        'correct' => 0,
                    ];
                    $topicScores[$topicKey]['total']++;
                    $topicScores[$topicKey]['correct'] += (int) $isCorrect;
                }

                $answerSnapshots[] = [
                    'question_id' => $question->id,
                    'category' => $categoryCode,
                    'category_name' => $categoryName,
                    'topic_id' => $question->topic_id,
                    'topic' => $topicName,
                    'prompt' => $question->question_text,
                    'choices' => $question->choices->map(fn ($choice): array => [
                        'label' => $choice->choice_label,
                        'text' => $choice->choice_text,
                    ])->values()->all(),
                    'selected_answer' => $selectedAnswer,
                    'correct_answer' => $question->correct_choice,
                    'is_correct' => $isCorrect,
                    'flagged' => (bool) ($answer['flagged'] ?? false),
                    'explanation' => $question->explanation ?? '',
                ];
            }

            $totalQuestions = count($answerSnapshots);
            $scorePercentage = round(($correctCount / $totalQuestions) * 100, 2);
            $completedAt = Carbon::parse($attemptData['completed_at'])->utc();
            $settings = $user->getOrCreateSettings();
            $activityDate = $completedAt->copy()->setTimezone($settings->timezone)->toDateString();

            $attempt = MobilePracticeAttempt::query()->create([
                'user_id' => $user->id,
                'client_attempt_id' => $attemptData['client_attempt_id'],
                'title' => $attemptData['title'],
                'mode' => $attemptData['mode'],
                'question_count' => $totalQuestions,
                'correct_count' => $correctCount,
                'score_percentage' => $scorePercentage,
                'passing_percentage' => (float) $attemptData['passing_percentage'],
                'duration_seconds' => (int) $attemptData['duration_seconds'],
                'completed_at' => $completedAt,
                'category_scores_json' => $categoryScores,
                'topic_scores_json' => array_values($topicScores),
                'answers_json' => $answerSnapshots,
            ]);

            StudyActivity::query()->create([
                'user_id' => $user->id,
                'exam_attempt_id' => null,
                'activity_date' => $activityDate,
                'activity_type' => $attemptData['mode'] === 'full'
                    ? ActivityType::FullExam->value
                    : ActivityType::CategoryPractice->value,
                'questions_count' => $totalQuestions,
                'duration_minutes' => (int) ceil(((int) $attemptData['duration_seconds']) / 60),
                'score_percentage' => $attempt->score_percentage,
            ]);

            return ['accepted' => true, 'message' => ''];
        });
    }
}
