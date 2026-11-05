<?php

declare(strict_types=1);

namespace App\Services\QuestionBank;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\QuestionChoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuestionManagementService
{
    /**
     * @param array{
     *     search?: ?string,
     *     category_id?: ?int,
     *     difficulty?: ?string,
     *     status?: ?string,
     *     per_page?: int,
     *     sort_by?: string,
     *     sort_dir?: string
     * } $filters
     * @return LengthAwarePaginator<Question>
     */
    public function getPaginatedQuestions(array $filters = []): LengthAwarePaginator
    {
        $query = Question::query()
            ->with(['category', 'choices', 'importBatch']);

        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('question_text', 'like', "%{$term}%")
                    ->orWhere('source', 'like', "%{$term}%")
                    ->orWhereHas('choices', function (Builder $cq) use ($term) {
                        $cq->where('choice_text', 'like', "%{$term}%");
                    });
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param array{
     *     category_id: int,
     *     question_text: string,
     *     choices: array{A: string, B: string, C: string, D: string},
     *     correct_choice: string,
     *     explanation?: ?string,
     *     difficulty?: string,
     *     source?: ?string,
     *     status?: string,
     *     import_batch_id?: ?int
     * } $data
     */
    public function createQuestion(array $data): Question
    {
        $this->validateChoicesData($data['choices'], $data['correct_choice']);

        return DB::transaction(function () use ($data) {
            $hash = Question::normalizeHash($data['question_text']);

            $question = Question::create([
                'category_id' => $data['category_id'],
                'import_batch_id' => $data['import_batch_id'] ?? null,
                'question_text' => trim($data['question_text']),
                'correct_choice' => strtoupper(trim($data['correct_choice'])),
                'explanation' => isset($data['explanation']) ? trim($data['explanation']) : null,
                'difficulty' => $data['difficulty'] ?? QuestionDifficulty::Medium->value,
                'source' => isset($data['source']) ? trim($data['source']) : null,
                'status' => $data['status'] ?? QuestionStatus::Published->value,
                'content_hash' => $hash,
            ]);

            foreach (['A', 'B', 'C', 'D'] as $label) {
                QuestionChoice::create([
                    'question_id' => $question->id,
                    'choice_label' => $label,
                    'choice_text' => trim($data['choices'][$label]),
                    'is_correct' => ($label === $question->correct_choice),
                ]);
            }

            return $question->load(['category', 'choices']);
        });
    }

    /**
     * @param array{
     *     category_id?: int,
     *     question_text?: string,
     *     choices?: array{A: string, B: string, C: string, D: string},
     *     correct_choice?: string,
     *     explanation?: ?string,
     *     difficulty?: string,
     *     source?: ?string,
     *     status?: string
     * } $data
     */
    public function updateQuestion(Question $question, array $data): Question
    {
        return DB::transaction(function () use ($question, $data) {
            $updates = [];

            if (isset($data['category_id'])) {
                $updates['category_id'] = $data['category_id'];
            }

            if (isset($data['question_text'])) {
                $updates['question_text'] = trim($data['question_text']);
                $updates['content_hash'] = Question::normalizeHash($data['question_text']);
            }

            if (isset($data['correct_choice'])) {
                $updates['correct_choice'] = strtoupper(trim($data['correct_choice']));
            }

            if (array_key_exists('explanation', $data)) {
                $updates['explanation'] = $data['explanation'] ? trim($data['explanation']) : null;
            }

            if (isset($data['difficulty'])) {
                $updates['difficulty'] = $data['difficulty'];
            }

            if (array_key_exists('source', $data)) {
                $updates['source'] = $data['source'] ? trim($data['source']) : null;
            }

            if (isset($data['status'])) {
                $updates['status'] = $data['status'];
            }

            $question->update($updates);

            if (isset($data['choices'])) {
                $targetCorrect = $updates['correct_choice'] ?? $question->correct_choice;
                $this->validateChoicesData($data['choices'], $targetCorrect);

                foreach (['A', 'B', 'C', 'D'] as $label) {
                    QuestionChoice::updateOrCreate(
                        [
                            'question_id' => $question->id,
                            'choice_label' => $label,
                        ],
                        [
                            'choice_text' => trim($data['choices'][$label]),
                            'is_correct' => ($label === $targetCorrect),
                        ]
                    );
                }
            } elseif (isset($updates['correct_choice'])) {
                foreach ($question->choices as $choice) {
                    $choice->update([
                        'is_correct' => ($choice->choice_label === $updates['correct_choice']),
                    ]);
                }
            }

            return $question->fresh(['category', 'choices']);
        });
    }

    public function archiveQuestion(Question $question): bool
    {
        return $question->update(['status' => QuestionStatus::Archived->value]);
    }

    public function restoreQuestion(Question $question): bool
    {
        return $question->update(['status' => QuestionStatus::Published->value]);
    }

    public function deleteQuestion(Question $question): ?bool
    {
        return $question->delete();
    }

    /**
     * Permanently delete every question (and their choices via cascade) from
     * the Question Bank. Returns the number of records deleted.
     *
     * Questions that are referenced by historical exam attempt snapshots are
     * safe to delete because the snapshot text lives in exam_attempt_questions
     * and is decoupled from the master question rows.
     */
    public function deleteAllQuestions(): int
    {
        return DB::transaction(function (): int {
            // Choices are deleted first (FK constraint) then questions.
            // QuestionChoice has a cascading delete at the DB level, but we
            // delete explicitly here to get an accurate count and to keep the
            // behaviour correct regardless of DB-level cascade configuration.
            QuestionChoice::query()->delete();

            return Question::query()->delete();
        });
    }

    /**
     * @param array<string, string> $choices
     */
    private function validateChoicesData(array $choices, string $correctChoice): void
    {
        foreach (['A', 'B', 'C', 'D'] as $label) {
            if (!isset($choices[$label]) || trim((string) $choices[$label]) === '') {
                throw new InvalidArgumentException("Choice {$label} is required.");
            }
        }

        $correct = strtoupper(trim($correctChoice));
        if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
            throw new InvalidArgumentException("Correct choice must be A, B, C, or D.");
        }
    }
}
