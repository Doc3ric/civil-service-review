<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('question_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw_data_json')->nullable();
            $table->string('category_code', 50)->nullable();
            $table->text('question_text')->nullable();
            $table->text('choice_a')->nullable();
            $table->text('choice_b')->nullable();
            $table->text('choice_c')->nullable();
            $table->text('choice_d')->nullable();
            $table->string('correct_answer', 10)->nullable();
            $table->string('difficulty', 20)->nullable();
            $table->string('source')->nullable();
            $table->string('status', 20)->default('valid');
            $table->json('error_messages_json')->nullable();
            $table->timestamps();

            $table->index(['import_batch_id', 'status']);
            $table->index(['import_batch_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_import_rows');
    }
};
