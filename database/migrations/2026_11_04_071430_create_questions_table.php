<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('exam_categories')->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('question_import_batches')->nullOnDelete();
            $table->text('question_text');
            $table->string('correct_choice', 5);
            $table->text('explanation')->nullable();
            $table->string('difficulty', 20)->default('medium');
            $table->string('source')->nullable();
            $table->string('status', 20)->default('published');
            $table->string('content_hash', 64)->index();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index(['status', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
