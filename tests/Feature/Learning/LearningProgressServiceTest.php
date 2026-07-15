<?php

namespace Tests\Feature\Learning;

use App\Models\LessonAnswer;
use App\Models\LessonExam;
use App\Models\LessonExamAttempt;
use App\Models\LessonMaterial;
use App\Models\LessonPortfolio;
use App\Models\LessonSection;
use App\Models\LessonTheme;
use App\Services\Learning\LearningProgressService;
use Illuminate\Support\Facades\DB;

class LearningProgressServiceTest extends LearningDatabaseTestCase
{
    public function test_portfolio_theme_requires_final_portfolio_status_for_completion(): void
    {
        $theme = LessonTheme::create([
            'title' => 'Portfolio Theme',
            'portfolio' => 1,
            'has_case_study' => 0,
        ]);
        $basicMaterial = LessonMaterial::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Basic',
            'priority' => 1,
            'has_understand' => 0,
            'material_type' => '基礎知識',
        ]);
        $understandingMaterial = LessonMaterial::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Understand',
            'priority' => 1,
            'has_understand' => 1,
            'material_type' => '基礎知識',
        ]);
        $portfolio = LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => 7,
            'status' => 2,
        ]);

        LessonAnswer::create([
            'material_id' => $basicMaterial->id,
            'user_id' => 7,
            'status' => 2,
        ]);
        LessonSection::create([
            'portfolio_id' => $portfolio->id,
            'material_id' => $understandingMaterial->id,
            'user_id' => 7,
            'status' => 2,
        ]);

        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);

        $this->assertTrue($progress['basic_completed']);
        $this->assertFalse($progress['portfolio']['completed']);
        $this->assertFalse($progress['theme_completed']);

        $portfolio->update(['status' => 3]);

        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);

        $this->assertTrue($progress['portfolio']['completed']);
        $this->assertTrue($progress['theme_completed']);
    }

    public function test_exam_progress_reports_attempt_limits_and_latest_result(): void
    {
        $theme = LessonTheme::create([
            'title' => 'Case Theme',
            'portfolio' => 0,
            'has_case_study' => 1,
        ]);
        $exam = LessonExam::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Exam',
            'passing_score' => 80,
            'max_attempts' => 2,
        ]);

        LessonExamAttempt::create([
            'lesson_exam_id' => $exam->id,
            'user_id' => 7,
            'score' => 40,
            'attempt_number' => 1,
            'status' => 'failed',
        ]);
        LessonExamAttempt::create([
            'lesson_exam_id' => $exam->id,
            'user_id' => 7,
            'score' => 70,
            'attempt_number' => 2,
            'status' => 'failed',
        ]);

        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);

        $this->assertTrue($progress['exam']['available']);
        $this->assertFalse($progress['exam']['passed']);
        $this->assertTrue($progress['exam']['exhausted']);
        $this->assertSame(2, $progress['exam']['attempts_count']);
        $this->assertSame(0, $progress['exam']['remaining_attempts']);
        $this->assertSame(70, $progress['exam']['latest_score']);
        $this->assertSame('failed', $progress['exam']['latest_status']);
        $this->assertFalse($progress['theme_completed']);
    }

    public function test_batch_progress_loads_multiple_users_without_reloading_theme_per_user(): void
    {
        $theme = LessonTheme::create([
            'title' => 'Batch Theme',
            'portfolio' => 1,
            'has_case_study' => 0,
        ]);
        $material = LessonMaterial::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Basic',
            'priority' => 1,
            'has_understand' => 0,
            'material_type' => '基礎知識',
        ]);
        $exam = LessonExam::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Exam',
            'passing_score' => 80,
            'max_attempts' => 2,
        ]);

        LessonAnswer::create([
            'material_id' => $material->id,
            'user_id' => 7,
            'status' => 2,
        ]);
        LessonAnswer::create([
            'material_id' => $material->id,
            'user_id' => 8,
            'status' => -1,
        ]);
        LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => 7,
            'status' => 3,
        ]);
        LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => 8,
            'status' => 0,
        ]);
        LessonExamAttempt::create([
            'lesson_exam_id' => $exam->id,
            'user_id' => 7,
            'score' => 90,
            'attempt_number' => 1,
            'status' => 'passed',
        ]);
        LessonExamAttempt::create([
            'lesson_exam_id' => $exam->id,
            'user_id' => 8,
            'score' => 30,
            'attempt_number' => 1,
            'status' => 'failed',
        ]);

        DB::enableQueryLog();

        $progressByUser = app(LearningProgressService::class)->forThemeUsers($theme->id, [7, 8]);

        $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
        $this->assertTrue($progressByUser[7]['basic_completed']);
        $this->assertTrue($progressByUser[7]['exam_passed']);
        $this->assertTrue($progressByUser[7]['portfolio']['completed']);
        $this->assertFalse($progressByUser[8]['basic_completed']);
        $this->assertTrue($progressByUser[8]['basic']['not_understood']);
        $this->assertFalse($progressByUser[8]['exam_passed']);
        $this->assertFalse($progressByUser[8]['portfolio']['completed']);
    }
}
