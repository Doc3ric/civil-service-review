<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\ExamCategory;
use App\Models\QuestionImportBatch;
use App\Models\User;
use App\Services\Examination\ExamGeneratorService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityAndAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $anotherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->user = User::firstOrFail();
        $this->anotherUser = User::factory()->create();
    }

    public function test_unauthenticated_requests_are_redirected_to_login(): void
    {
        $protectedRoutes = [
            '/dashboard',
            '/questions',
            '/questions/import',
            '/exams/full',
            '/exams/category',
            '/exams/history',
            '/analytics',
            '/streak-calendar',
            '/settings',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    public function test_user_cannot_access_another_users_active_examination(): void
    {
        $generator = app(ExamGeneratorService::class);
        $category = ExamCategory::firstOrFail();
        $attempt = $generator->generateCategoryPractice($this->user, $category, 5);

        // Attempt to access with another user
        $response = $this->actingAs($this->anotherUser)->get(route('exams.runner', ['attempt' => $attempt->id]));
        $response->assertStatus(403);
    }

    public function test_active_examination_runner_does_not_reveal_correct_answers_to_client(): void
    {
        $generator = app(ExamGeneratorService::class);
        $category = ExamCategory::firstOrFail();
        $attempt = $generator->generateCategoryPractice($this->user, $category, 5);

        $response = $this->actingAs($this->user)->get(route('exams.runner', ['attempt' => $attempt->id]));
        $response->assertOk();

        // Ensure "Correct Answer" indicator or text is NOT rendered anywhere in the active exam HTML
        $response->assertDontSee('Correct Answer');
        $response->assertDontSee('Explanation & Rationale');
    }

    public function test_login_rate_limiting_locks_out_after_five_failed_attempts(): void
    {
        RateLimiter::clear('badlogin@example.com|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'badlogin@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt should be locked out
        $response = $this->post('/login', [
            'email' => 'badlogin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $error = session('errors')->first('email');
        $this->assertStringContainsString('Too many login attempts', $error);
    }

    public function test_user_cannot_download_another_users_error_report(): void
    {
        $batch = QuestionImportBatch::create([
            'user_id' => $this->user->id,
            'original_filename' => 'secret_batch.xlsx',
            'status' => 'completed',
        ]);

        // Attempt download as another user
        $response = $this->actingAs($this->anotherUser)
            ->get(route('questions.import.error-report', ['batch' => $batch->id]));

        $response->assertStatus(403);
    }
}
