<?php

namespace Tests\Feature\Learning;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class LearningDatabaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createLearningSchema();
    }

    protected function createLearningSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('icon_path')->nullable();
            $table->string('icon_bg')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('evaluation_records', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('year')->nullable();
            $table->string('which_half')->nullable();
            $table->string('general_position')->nullable();
            $table->string('current_salary_rank')->nullable();
            $table->boolean('temp_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lesson_themes', function ($table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->integer('portfolio')->nullable();
            $table->integer('has_case_study')->nullable();
            $table->integer('custom_form_id')->nullable();
            $table->integer('previous_version')->nullable();
            $table->timestamps();
        });

        Schema::create('lesson_materials', function ($table) {
            $table->increments('id');
            $table->integer('lesson_theme_id');
            $table->unsignedBigInteger('lesson_material_version_id')->nullable();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->text('content_detailed')->nullable();
            $table->integer('priority')->default(1);
            $table->integer('has_understand')->default(0);
            $table->string('material_type')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lesson_material_versions', function ($table) {
            $table->increments('id');
            $table->integer('lesson_theme_id');
            $table->unsignedInteger('version_no');
            $table->boolean('is_default')->default(false);
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('lesson_answers', function ($table) {
            $table->increments('id');
            $table->integer('material_id');
            $table->integer('user_id');
            $table->text('answer')->nullable();
            $table->text('cant_understand')->nullable();
            $table->text('reason_dnt_und')->nullable();
            $table->integer('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lesson_portfolios', function ($table) {
            $table->increments('id');
            $table->integer('lesson_theme_id');
            $table->integer('attempt_no')->default(1);
            $table->integer('salary_issue_id')->nullable();
            $table->integer('user_id');
            $table->integer('status')->default(0);
            $table->text('content')->nullable();
            $table->text('episode')->nullable();
            $table->text('public_title')->nullable();
            $table->text('public_content')->nullable();
            $table->text('positive_feedback')->nullable();
            $table->text('negative_feedback')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lesson_sections', function ($table) {
            $table->increments('id');
            $table->integer('portfolio_id');
            $table->integer('material_id');
            $table->integer('user_id');
            $table->text('content')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });

        Schema::create('lesson_personal_materials', function ($table) {
            $table->increments('id');
            $table->integer('lesson_theme_id');
            $table->integer('user_id');
            $table->integer('lesson_theme_ai_config_id')->nullable();
            $table->string('config_key');
            $table->longText('content')->nullable();
            $table->text('presentation_spec')->nullable();
            $table->string('presentation_theme')->nullable();
            $table->string('presentation_path')->nullable();
            $table->text('source_snapshot')->nullable();
            $table->boolean('understand')->nullable();
            $table->longText('important_point')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lesson_forms', function ($table) {
            $table->increments('id');
            $table->integer('lesson_theme_id');
            $table->integer('user_id');
            $table->text('question1')->nullable();
            $table->text('answer1')->nullable();
            $table->text('question2')->nullable();
            $table->text('answer2')->nullable();
            $table->text('question3')->nullable();
            $table->text('answer3')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();
        });

        Schema::create('lesson_exams', function ($table) {
            $table->increments('id');
            $table->integer('lesson_theme_id');
            $table->string('title')->nullable();
            $table->integer('passing_score')->default(80);
            $table->integer('max_attempts')->default(1);
            $table->timestamps();
        });

        Schema::create('lesson_exam_attempts', function ($table) {
            $table->increments('id');
            $table->integer('lesson_exam_id');
            $table->integer('user_id');
            $table->integer('score');
            $table->integer('attempt_number');
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lesson_summaries', function ($table) {
            $table->increments('id');
            $table->integer('lesson_material_id');
            $table->string('summary_title')->nullable();
            $table->text('summary_content')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lesson_summary_questions', function ($table) {
            $table->increments('id');
            $table->integer('lesson_summary_id');
            $table->text('question')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lesson_summary_answers', function ($table) {
            $table->increments('id');
            $table->integer('lesson_summary_id');
            $table->integer('lesson_summary_question_id');
            $table->integer('user_id');
            $table->text('answer_val')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('custom_forms', function ($table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('survey_answers', function ($table) {
            $table->increments('id');
            $table->integer('custom_form_id');
            $table->integer('user_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
