<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lesson_exams', function (Blueprint $table) {
            $table->id();
            $table->integer('lesson_theme_id');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('passing_score')->default(80); // percentage
            $table->unsignedInteger('max_attempts')->default(1);
            $table->foreign('lesson_theme_id')
                ->references('id')
                ->on('lesson_themes')
                ->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lesson_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_exam_id')->constrained('lesson_exams')->cascadeOnDelete();
            $table->text('prompt');
            $table->text('explanation')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('lesson_exam_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_exam_question_id')->constrained('lesson_exam_questions')->cascadeOnDelete();
            $table->text('label');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        Schema::create('lesson_exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_exam_id')->constrained('lesson_exams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0); // percentage
            $table->unsignedInteger('attempt_number')->default(1);
            $table->enum('status', ['passed', 'failed'])->default('failed');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lesson_exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_exam_attempt_id')->constrained('lesson_exam_attempts')->cascadeOnDelete();
            $table->foreignId('lesson_exam_question_id')->constrained('lesson_exam_questions')->cascadeOnDelete();
            $table->foreignId('lesson_exam_option_id')->nullable()->constrained('lesson_exam_options')->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(
                ['lesson_exam_attempt_id', 'lesson_exam_question_id'],
                'exam_answer_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_exam_answers');
        Schema::dropIfExists('lesson_exam_attempts');
        Schema::dropIfExists('lesson_exam_options');
        Schema::dropIfExists('lesson_exam_questions');
        Schema::dropIfExists('lesson_exams');
    }
};
