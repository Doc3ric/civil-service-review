<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exam_blueprint_id',
        'category_id',
        'title',
        'mode',
        'total_questions',
        'correct_count',
        'incorrect_count',
        'unanswered_count',
        'score_percentage',
        'status',
        'duration_minutes',
        'started_at',
        'deadline_at',
        'submitted_at',
        'category_scores_json',
    ];

    protected function casts(): array
    {
        return [
            'mode' => ExamMode::class,
            'status' => ExamStatus::class,
            'total_questions' => 'integer',
            'correct_count' => 'integer',
            'incorrect_count' => 'integer',
            'unanswered_count' => 'integer',
            'score_percentage' => 'decimal:2',
            'duration_minutes' => 'integer',
            'started_at' => 'datetime',
            'deadline_at' => 'datetime',
            'submitted_at' => 'datetime',
            'category_scores_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<ExamBlueprint, $this>
     */
    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(ExamBlueprint::class, 'exam_blueprint_id');
    }

    /**
     * @return BelongsTo<ExamCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'category_id');
    }

    /**
     * @return HasMany<ExamAttemptQuestion, $this>
     */
    public function attemptQuestions(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestion::class, 'exam_attempt_id')->orderBy('question_number');
    }

    /**
     * @return HasMany<ExamAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'exam_attempt_id');
    }

    /**
     * @return HasOne<StudyActivity, $this>
     */
    public function studyActivity(): HasOne
    {
        return $this->hasOne(StudyActivity::class, 'exam_attempt_id');
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [ExamStatus::Completed, ExamStatus::Expired], true);
    }

    public function isExpired(): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        if ($this->deadline_at === null) {
            return false;
        }

        return Carbon::now()->greaterThanOrEqualTo($this->deadline_at);
    }

    public function remainingSeconds(): int
    {
        if ($this->deadline_at === null) {
            return 0;
        }

        $now = Carbon::now();
        if ($now->greaterThanOrEqualTo($this->deadline_at)) {
            return 0;
        }

        return (int) $now->diffInSeconds($this->deadline_at, false);
    }
}
