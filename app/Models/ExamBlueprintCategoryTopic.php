<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamBlueprintCategoryTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_blueprint_category_id',
        'topic_id',
        'question_count',
    ];

    protected function casts(): array
    {
        return [
            'question_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ExamBlueprintCategory, $this>
     */
    public function blueprintCategory(): BelongsTo
    {
        return $this->belongsTo(ExamBlueprintCategory::class, 'exam_blueprint_category_id');
    }

    /**
     * @return BelongsTo<QuestionTopic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(QuestionTopic::class, 'topic_id');
    }
}
