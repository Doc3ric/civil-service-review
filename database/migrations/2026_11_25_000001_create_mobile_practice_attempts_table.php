<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_practice_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_attempt_id', 36);
            $table->string('title', 120);
            $table->string('mode', 20);
            $table->unsignedSmallInteger('question_count');
            $table->unsignedSmallInteger('correct_count');
            $table->decimal('score_percentage', 5, 2);
            $table->decimal('passing_percentage', 5, 2);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('completed_at');
            $table->json('category_scores_json');
            $table->json('topic_scores_json');
            $table->json('answers_json');
            $table->timestamps();

            $table->unique(['user_id', 'client_attempt_id']);
            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_practice_attempts');
    }
};
