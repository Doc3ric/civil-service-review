<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'color',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'code' => CategoryCode::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'category_id');
    }

    /**
     * @return HasMany<ExamBlueprintCategory, $this>
     */
    public function blueprintCategories(): HasMany
    {
        return $this->hasMany(ExamBlueprintCategory::class, 'category_id');
    }

    /**
     * @return HasMany<ExamAttempt, $this>
     */
    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'category_id');
    }
}
