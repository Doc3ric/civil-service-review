<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('exam_blueprint_id')->nullable()->constrained('exam_blueprints')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('exam_categories')->nullOnDelete();
            $table->string('title');
            $table->string('mode', 20)->default('full');
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('incorrect_count')->default(0);
            $table->unsignedInteger('unanswered_count')->default(0);
            $table->decimal('score_percentage', 5, 2)->default(0.00);
            $table->string('status', 20)->default('in_progress');
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->json('category_scores_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'submitted_at']);
            $table->index(['mode', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
