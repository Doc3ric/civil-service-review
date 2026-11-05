<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportRowStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionImportRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'raw_data_json',
        'category_code',
        'question_text',
        'choice_a',
        'choice_b',
        'choice_c',
        'choice_d',
        'correct_answer',
        'difficulty',
        'source',
        'status',
        'error_messages_json',
    ];

    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'status' => ImportRowStatus::class,
            'raw_data_json' => 'array',
            'error_messages_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<QuestionImportBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(QuestionImportBatch::class, 'import_batch_id');
    }
}
