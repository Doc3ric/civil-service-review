<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryCode;
use App\Models\ExamCategory;
use Illuminate\Database\Seeder;

class ExamCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => CategoryCode::Verbal->value,
                'name' => 'Verbal Ability',
                'description' => 'Grammar, correct usage, vocabulary, synonyms, antonyms, paragraph organization, and reading comprehension.',
                'icon' => 'book-open',
                'color' => '#174A8B',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => CategoryCode::Analytical->value,
                'name' => 'Analytical Ability',
                'description' => 'Word analogy, identifying assumptions, logic, syllogism, and data interpretation.',
                'icon' => 'brain',
                'color' => '#C92535',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => CategoryCode::Numerical->value,
                'name' => 'Numerical Ability',
                'description' => 'Basic mathematical operations, fractions, percentages, ratio and proportion, algebraic problems, and number sequences.',
                'icon' => 'calculator',
                'color' => '#2563EB',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => CategoryCode::General->value,
                'name' => 'General Information',
                'description' => 'Philippine Constitution, Code of Conduct (RA 6713), Human Rights, and Environmental Protection issues.',
                'icon' => 'award',
                'color' => '#0D9488',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            ExamCategory::updateOrCreate(['code' => $cat['code']], $cat);
        }
    }
}
