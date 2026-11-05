<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<ExamAttempt, $this>
     */
    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'user_id');
    }

    /**
     * @return HasMany<StudyActivity, $this>
     */
    public function studyActivities(): HasMany
    {
        return $this->hasMany(StudyActivity::class, 'user_id');
    }

    /**
     * @return HasOne<UserSetting, $this>
     */
    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class, 'user_id');
    }

    /**
     * @return HasMany<QuestionImportBatch, $this>
     */
    public function importBatches(): HasMany
    {
        return $this->hasMany(QuestionImportBatch::class, 'user_id');
    }

    /**
     * Ensure user settings exist.
     */
    public function getOrCreateSettings(): UserSetting
    {
        /** @var UserSetting */
        return $this->settings()->firstOrCreate(
            ['user_id' => $this->id],
            [
                'daily_question_target' => 20,
                'daily_study_goal_minutes' => 30,
                'timezone' => 'Asia/Manila',
                'preferences_json' => [],
            ]
        );
    }
}
