<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exam_attempt_id',
        'activity_date',
        'activity_type',
        'questions_count',
        'duration_minutes',
        'score_percentage',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'activity_type' => ActivityType::class,
            'questions_count' => 'integer',
            'duration_minutes' => 'integer',
            'score_percentage' => 'decimal:2',
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
     * @return BelongsTo<ExamAttempt, $this>
     */
    public function examAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
}
