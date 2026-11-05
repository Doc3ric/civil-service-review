<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->foreignId('category_id')->constrained('exam_categories')->cascadeOnDelete();
            $table->unsignedInteger('question_number');
            $table->text('question_text_snapshot');
            $table->string('category_name_snapshot');
            $table->text('explanation_snapshot')->nullable();
            $table->string('correct_choice_label_snapshot', 5);
            $table->json('shuffled_choices_json');
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'question_number']);
            $table->index(['exam_attempt_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_questions');
    }
};
