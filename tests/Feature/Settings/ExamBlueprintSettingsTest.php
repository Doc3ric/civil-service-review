<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Livewire\Settings\SettingsManager;
use App\Models\ExamBlueprint;
use App\Models\ExamCategory;
use App\Models\User;
use Database\Seeders\ExamBlueprintSeeder;
use Database\Seeders\ExamCategorySeeder;
use Database\Seeders\QuestionTopicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExamBlueprintSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            ExamCategorySeeder::class,
            ExamBlueprintSeeder::class,
            QuestionTopicSeeder::class,
        ]);
    }

    public function test_blueprint_settings_can_be_saved(): void
    {
        $user = User::factory()->create();
        $blueprint = ExamBlueprint::firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsManager::class)
            ->set('examName', 'My Civil Service Practice Exam')
            ->call('saveExamSettings')
            ->assertHasNoErrors()
            ->assertSee('Examination blueprint settings updated successfully.');

        $this->assertDatabaseHas('exam_blueprints', [
            'id' => $blueprint->id,
            'name' => 'My Civil Service Practice Exam',
            'total_questions' => 150,
        ]);
    }

    public function test_blueprint_rejects_unknown_category_ids_without_writing(): void
    {
        $user = User::factory()->create();
        $blueprint = ExamBlueprint::firstOrFail();
        $allocations = $blueprint->blueprintCategories()->pluck('question_count', 'category_id')->all();
        $firstCategoryId = (int) array_key_first($allocations);
        $allocations[$firstCategoryId]--;
        $allocations[999999] = 1;

        Livewire::actingAs($user)
            ->test(SettingsManager::class)
            ->set('categoryAllocations', $allocations)
            ->call('saveExamSettings')
            ->assertHasErrors('categoryAllocations');

        $this->assertDatabaseHas('exam_blueprints', [
            'id' => $blueprint->id,
            'name' => $blueprint->name,
        ]);
        $this->assertSame(4, $blueprint->blueprintCategories()->count());
        $this->assertFalse(ExamCategory::whereKey(999999)->exists());
    }

    public function test_data_backup_settings_panel_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SettingsManager::class)
            ->set('activeTab', 'data')
            ->assertSee('Question Bank Backups')
            ->assertSee('Create Backup Now');
    }
}
