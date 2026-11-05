<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'storage_path',
        'total_rows',
        'imported_rows',
        'invalid_rows',
        'duplicate_rows',
        'skipped_rows',
        'status',
        'error_summary_json',
    ];

    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'total_rows' => 'integer',
            'imported_rows' => 'integer',
            'invalid_rows' => 'integer',
            'duplicate_rows' => 'integer',
            'skipped_rows' => 'integer',
            'error_summary_json' => 'array',
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
     * @return HasMany<QuestionImportRow, $this>
     */
    public function rows(): HasMany
    {
        return $this->hasMany(QuestionImportRow::class, 'import_batch_id')->orderBy('row_number');
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'import_batch_id');
    }
}
