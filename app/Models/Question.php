<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'import_batch_id',
        'question_text',
        'correct_choice',
        'explanation',
        'difficulty',
        'source',
        'status',
        'content_hash',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => QuestionDifficulty::class,
            'status' => QuestionStatus::class,
        ];
    }

    /**
     * Compute a normalized SHA-256 hash for duplicate detection.
     */
    public static function normalizeHash(string $questionText): string
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $questionText) ?? ''));
        return hash('sha256', $normalized);
    }

    /**
     * @return BelongsTo<ExamCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<QuestionImportBatch, $this>
     */
    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(QuestionImportBatch::class, 'import_batch_id');
    }

    /**
     * @return HasMany<QuestionChoice, $this>
     */
    public function choices(): HasMany
    {
        return $this->hasMany(QuestionChoice::class, 'question_id')->orderBy('choice_label');
    }

    /**
     * @return HasMany<ExamAttemptQuestion, $this>
     */
    public function attemptQuestions(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestion::class, 'question_id');
    }

    /**
     * @param Builder<Question> $query
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', QuestionStatus::Published->value);
    }

    /**
     * @param Builder<Question> $query
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', QuestionStatus::Draft->value);
    }

    /**
     * @param Builder<Question> $query
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', QuestionStatus::Archived->value);
    }
}
