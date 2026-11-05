<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamAttemptQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'category_id',
        'question_number',
        'question_text_snapshot',
        'category_name_snapshot',
        'explanation_snapshot',
        'correct_choice_label_snapshot',
        'shuffled_choices_json',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'shuffled_choices_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ExamAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    /**
     * @return BelongsTo<ExamCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'category_id');
    }

    /**
     * @return HasOne<ExamAnswer, $this>
     */
    public function answer(): HasOne
    {
        return $this->hasOne(ExamAnswer::class, 'exam_attempt_question_id');
    }
}
