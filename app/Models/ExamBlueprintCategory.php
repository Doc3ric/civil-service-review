<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamBlueprintCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_blueprint_id',
        'category_id',
        'question_count',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'question_count' => 'integer',
            'percentage' => 'decimal:2',
        ];
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ExamBlueprintCategoryTopic, $this>
     */
    public function topicAllocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamBlueprintCategoryTopic::class, 'exam_blueprint_category_id')->with('topic');
    }
}
