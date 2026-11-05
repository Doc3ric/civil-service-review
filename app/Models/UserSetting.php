<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'daily_question_target',
        'daily_study_goal_minutes',
        'timezone',
        'preferences_json',
    ];

    protected function casts(): array
    {
        return [
            'daily_question_target' => 'integer',
            'daily_study_goal_minutes' => 'integer',
            'preferences_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
