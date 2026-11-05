<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryCode;
use App\Models\ExamBlueprint;
use App\Models\ExamBlueprintCategory;
use App\Models\ExamCategory;
use Illuminate\Database\Seeder;

class ExamBlueprintSeeder extends Seeder
{
    public function run(): void
    {
        $blueprint = ExamBlueprint::updateOrCreate(
            ['name' => 'Civil Service Professional Level — 150 Items'],
            [
                'exam_type' => 'full',
                'total_questions' => 150,
                'duration_minutes' => 170,
                'passing_percentage' => 80.00,
                'description' => 'Comprehensive standard practice examination for the Philippine Civil Service Commission (CSC) Professional Level Examination.',
                'is_active' => true,
            ]
        );

        $allocations = [
            CategoryCode::Verbal->value => ['count' => 45, 'percentage' => 30.00],
            CategoryCode::Analytical->value => ['count' => 53, 'percentage' => 35.33],
            CategoryCode::Numerical->value => ['count' => 45, 'percentage' => 30.00],
            CategoryCode::General->value => ['count' => 7, 'percentage' => 4.67],
        ];

        foreach ($allocations as $code => $data) {
            $category = ExamCategory::where('code', $code)->first();
            if ($category !== null) {
                ExamBlueprintCategory::updateOrCreate(
                    [
                        'exam_blueprint_id' => $blueprint->id,
                        'category_id' => $category->id,
                    ],
                    [
                        'question_count' => $data['count'],
                        'percentage' => $data['percentage'],
                    ]
                );
            }
        }
    }
}
