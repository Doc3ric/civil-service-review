<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilePracticeAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_attempt_id',
        'title',
        'mode',
        'question_count',
        'correct_count',
        'score_percentage',
        'passing_percentage',
        'duration_seconds',
        'completed_at',
        'category_scores_json',
        'topic_scores_json',
        'answers_json',
    ];

    protected function casts(): array
    {
        return [
            'question_count' => 'integer',
            'correct_count' => 'integer',
            'score_percentage' => 'float',
            'passing_percentage' => 'float',
            'duration_seconds' => 'integer',
            'completed_at' => 'datetime',
            'category_scores_json' => 'array',
            'topic_scores_json' => 'array',
            'answers_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
