<?php

declare(strict_types=1);

namespace App\Services\Progress;

use App\Enums\ActivityType;
use App\Enums\ExamMode;
use App\Models\ExamAttempt;
use App\Models\StudyActivity;
use App\Models\User;
use Carbon\Carbon;

class StudyActivityService
{
    /**
     * Record study activity from a completed examination attempt.
     */
    public function recordExamActivity(ExamAttempt $attempt): StudyActivity
    {
        $timezone = $attempt->user->settings->timezone ?? 'Asia/Manila';
        $submissionDate = $attempt->submitted_at
            ? (clone $attempt->submitted_at)->setTimezone($timezone)->format('Y-m-d')
            : Carbon::now($timezone)->format('Y-m-d');

        $activityType = ($attempt->mode === ExamMode::Full)
            ? ActivityType::FullExam
            : ActivityType::CategoryPractice;

        $duration = 0;
        if ($attempt->started_at && $attempt->submitted_at) {
            $duration = (int) max(1, round($attempt->started_at->diffInMinutes($attempt->submitted_at)));
        }

        return StudyActivity::updateOrCreate(
            [
                'user_id' => $attempt->user_id,
                'activity_date' => $submissionDate,
                'exam_attempt_id' => $attempt->id,
            ],
            [
                'activity_type' => $activityType->value,
                'questions_count' => $attempt->total_questions,
                'duration_minutes' => $duration,
                'score_percentage' => $attempt->score_percentage,
            ]
        );
    }
}
