<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = config('app.env') === 'production'
            ? env('INITIAL_REVIEWER_PASSWORD', Str::random(24))
            : env('INITIAL_REVIEWER_PASSWORD', 'Reviewer2027!');

        $user = User::firstOrCreate(
            ['email' => 'reviewer@csc2027.ph'],
            [
                'name' => 'Civil Service Reviewer',
                'password' => Hash::make($defaultPassword),
                'email_verified_at' => now(),
            ]
        );

        UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'daily_question_target' => 20,
                'daily_study_goal_minutes' => 30,
                'timezone' => 'Asia/Manila',
                'preferences_json' => [
                    'theme' => 'light',
                    'sound_effects' => false,
                    'auto_advance' => true,
                ],
            ]
        );
    }
}
