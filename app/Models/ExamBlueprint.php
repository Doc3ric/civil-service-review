<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamBlueprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'exam_type',
        'total_questions',
        'duration_minutes',
        'passing_percentage',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_questions' => 'integer',
            'duration_minutes' => 'integer',
            'passing_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ExamBlueprintCategory, $this>
     */
    public function blueprintCategories(): HasMany
    {
        return $this->hasMany(ExamBlueprintCategory::class, 'exam_blueprint_id');
    }

    /**
     * @return HasMany<ExamAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'exam_blueprint_id');
    }
}
