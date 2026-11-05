<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_blueprint_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_blueprint_id')->constrained('exam_blueprints')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('exam_categories')->cascadeOnDelete();
            $table->unsignedInteger('question_count');
            $table->decimal('percentage', 5, 2);
            $table->timestamps();

            $table->unique(['exam_blueprint_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_blueprint_categories');
    }
};
