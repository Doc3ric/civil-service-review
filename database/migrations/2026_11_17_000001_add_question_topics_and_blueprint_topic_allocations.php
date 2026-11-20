<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('exam_categories')->cascadeOnDelete();
            $table->string('name', 120);
            $table->timestamps();

            $table->unique(['category_id', 'name']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('topic_id')->nullable()->after('category_id')->constrained('question_topics')->nullOnDelete();
            $table->index(['category_id', 'topic_id', 'status']);
        });

        Schema::table('question_import_rows', function (Blueprint $table) {
            $table->string('topic_name', 120)->nullable()->after('category_code');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->foreignId('topic_id')->nullable()->after('category_id')->constrained('question_topics')->nullOnDelete();
        });

        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->foreignId('topic_id')->nullable()->after('category_id')->constrained('question_topics')->nullOnDelete();
            $table->string('topic_name_snapshot')->nullable()->after('category_name_snapshot');
            $table->index(['exam_attempt_id', 'topic_id']);
        });

        Schema::create('exam_blueprint_category_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_blueprint_category_id')->constrained('exam_blueprint_categories')->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('question_topics')->cascadeOnDelete();
            $table->unsignedInteger('question_count');
            $table->timestamps();

            $table->unique(['exam_blueprint_category_id', 'topic_id'], 'blueprint_category_topic_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_blueprint_category_topics');

        Schema::table('question_import_rows', function (Blueprint $table) {
            $table->dropColumn('topic_name');
        });

        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->dropIndex(['exam_attempt_id', 'topic_id']);
            $table->dropConstrainedForeignId('topic_id');
            $table->dropColumn('topic_name_snapshot');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('topic_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'topic_id', 'status']);
            $table->dropConstrainedForeignId('topic_id');
        });

        Schema::dropIfExists('question_topics');
    }
};
