<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ExamCategory;
use App\Models\QuestionTopic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionTopicSeeder extends Seeder
{
    /**
     * @var array<string, array<int, string>>
     */
    private const TOPICS_BY_CATEGORY = [
        'GENERAL' => [
            'BASIC CONSTITUTIONAL PRINCIPLES',
            'CODE OF CONDUCT AND ETHICAL STANDARDS',
            'PEACE AND HUMAN RIGHTS ISSUES AND CONCEPTS',
            'ENVIRONMENTAL MANAGEMENT AND PROTECTION',
            'CURRENT EVENTS & OTHER GENERAL INFORMATION',
        ],
        'VERBAL' => [
            'SYNONYMS',
            'ANTONYMS',
            'CORRECT USAGE',
            'IDENTIFYING ERRORS',
            'IDIOMATIC EXPRESSIONS',
            'READING COMPREHENSION',
            'PARAGRAPH ORGANIZATION',
        ],
        'ANALYTICAL' => [
            'WORD ASSOCIATION',
            'SINGLE-WORD ANALOGY',
            'DOUBLE-WORD ANALOGY',
            'LOGIC',
            'ASSUMPTION AND CONCLUSION',
            'DATA INTERPRETATION',
            'ABSTRACT REASONING WITH TIPS & EXPLANATIONS',
        ],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const RENAMED_TOPICS = [
        'VERBAL' => [
            'ERROR RECOGNITION' => 'IDENTIFYING ERRORS',
            'GRAMMAR AND USAGE' => 'CORRECT USAGE',
        ],
    ];

    public function run(): void
    {
        foreach (self::TOPICS_BY_CATEGORY as $categoryCode => $topicNames) {
            $category = ExamCategory::where('code', $categoryCode)->first();

            if ($category === null) {
                continue;
            }

            foreach (self::RENAMED_TOPICS[$categoryCode] ?? [] as $oldName => $newName) {
                $oldTopic = $category->topics()->where('name', $oldName)->first();
                $newTopicExists = $category->topics()->where('name', $newName)->exists();

                if ($oldTopic !== null && !$newTopicExists) {
                    $oldTopic->update(['name' => $newName]);
                }
            }

            foreach ($topicNames as $name) {
                QuestionTopic::firstOrCreate([
                    'category_id' => $category->id,
                    'name' => $name,
                ]);
            }

            foreach ($category->topics()->whereNotIn('name', $topicNames)->get() as $obsoleteTopic) {
                if (!$this->hasReferences($obsoleteTopic)) {
                    $obsoleteTopic->delete();
                }
            }
        }
    }

    private function hasReferences(QuestionTopic $topic): bool
    {
        return $topic->questions()->withTrashed()->exists()
            || DB::table('exam_blueprint_category_topics')->where('topic_id', $topic->id)->exists()
            || DB::table('exam_attempts')->where('topic_id', $topic->id)->exists()
            || DB::table('exam_attempt_questions')->where('topic_id', $topic->id)->exists();
    }
}
