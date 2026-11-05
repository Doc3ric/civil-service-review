<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('exam_attempt_question_id')->constrained('exam_attempt_questions')->cascadeOnDelete();
            $table->string('selected_choice_label', 5)->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'exam_attempt_question_id']);
            $table->index(['exam_attempt_id', 'is_correct']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};
