<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryCode;
use App\Models\ExamCategory;
use App\Models\QuestionTopic;
use Illuminate\Database\Seeder;

class QuestionTopicSeeder extends Seeder
{
    public function run(): void
    {
        $verbalCategory = ExamCategory::where('code', CategoryCode::Verbal->value)->first();

        if ($verbalCategory === null) {
            return;
        }

        foreach ([
            'ANTONYMS',
            'SYNONYMS',
            'ERROR RECOGNITION',
            'FILL IN THE BLANKS',
            'READING COMPREHENSION',
            'GRAMMAR AND USAGE',
            'SENTENCE COMPLETION',
            'VOCABULARY IN CONTEXT',
        ] as $name) {
            QuestionTopic::firstOrCreate([
                'category_id' => $verbalCategory->id,
                'name' => $name,
            ]);
        }
    }
}
