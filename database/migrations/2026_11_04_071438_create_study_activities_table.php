<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('exam_attempt_id')->nullable()->constrained('exam_attempts')->nullOnDelete();
            $table->date('activity_date');
            $table->string('activity_type', 30)->default('full_exam');
            $table->unsignedInteger('questions_count')->default(0);
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->decimal('score_percentage', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'activity_date', 'exam_attempt_id']);
            $table->index(['user_id', 'activity_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_activities');
    }
};
