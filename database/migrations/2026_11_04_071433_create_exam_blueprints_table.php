<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_blueprints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('exam_type', 30)->default('full');
            $table->unsignedInteger('total_questions')->default(150);
            $table->unsignedInteger('duration_minutes')->default(170);
            $table->decimal('passing_percentage', 5, 2)->default(80.00);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_blueprints');
    }
};
