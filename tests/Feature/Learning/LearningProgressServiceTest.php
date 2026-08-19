<?php

namespace Tests\Feature\Learning;

use App\Models\CustomForm;
use App\Models\LessonAnswer;
use App\Models\LessonExam;
use App\Models\LessonExamAttempt;
use App\Models\LessonMaterial;
use App\Models\LessonPersonalMaterial;
use App\Models\LessonPledgeSignature;
use App\Models\LessonPortfolio;
use App\Models\LessonSection;
use App\Models\LessonTheme;
use App\Models\SurveyAnswer;
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

    public function test_a_finished_portfolio_counts_basic_as_complete_without_section_rows(): void
    {
        // Legacy completions (finished before per-section records were kept) have
        // no lesson_sections rows. The portfolio step is unreachable until the
        // sections are done, so a finished portfolio proves basic was cleared.
        $theme = LessonTheme::create([
            'title' => 'Legacy Completed Theme',
            'portfolio' => 1,
            'has_case_study' => 0,
        ]);
        LessonMaterial::create([
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

        // Mid-flow: no section rows yet, so basic is not complete.
        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);
        $this->assertFalse($progress['basic_completed']);

        $portfolio->update(['status' => 3]);

        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);

        $this->assertTrue($progress['basic_completed']);
        $this->assertTrue($progress['theme_completed']);
    }

    public function test_ai_attempt_completes_basic_from_the_personal_material(): void
    {
        // On an AI attempt (attempt_no > 1) the 知識研修 stage is the generated
        // 個別研修資料, not the basic sections. Its completion lives on the
        // personal material keyed repeater_attempt_{portfolioId}.
        $theme = LessonTheme::create([
            'title' => 'Salary Challenge Theme',
            'portfolio' => 1,
            'has_case_study' => 0,
        ]);
        LessonMaterial::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Basic',
            'priority' => 1,
            'has_understand' => 1,
            'material_type' => '基礎知識',
        ]);
        LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => 7,
            'status' => 3,
            'attempt_no' => 1,
        ]);
        // Still in progress, so the finished-portfolio shortcut does not apply
        // and the personal material is the only thing that can clear basic.
        $current = LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => 7,
            'status' => 1,
            'attempt_no' => 2,
            'salary_issue_id' => 42,
        ]);

        // No personal material yet → basic not complete.
        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);
        $this->assertFalse($progress['basic_completed']);

        LessonPersonalMaterial::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => 7,
            'config_key' => 'repeater_attempt_'.$current->id,
            'content' => '# 個別研修資料',
            'understand' => true,
        ]);

        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);

        $this->assertTrue($progress['basic_completed']);

        // Finishing the attempt then completes the theme.
        $current->update(['status' => 3]);
        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);
        $this->assertTrue($progress['theme_completed']);
    }

    public function test_a_failed_exam_still_unlocks_the_checklist_and_completes_the_theme(): void
    {
        // Advancement requires the exam to have been TAKEN, not passed.
        [$theme, $exam] = $this->themeWithExamAndChecklist();

        // Not taken yet -> checklist locked, theme incomplete.
        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);
        $this->assertSame(0, $progress['exam']['attempts_count']);
        $this->assertFalse($progress['survey']['available']);
        $this->assertFalse($progress['theme_completed']);

        // One FAILED attempt is enough to advance.
        LessonExamAttempt::create([
            'lesson_exam_id' => $exam->id,
            'user_id' => 7,
            'score' => 10,
            'attempt_number' => 1,
            'status' => 'failed',
        ]);

        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);

        $this->assertFalse($progress['exam']['passed']);
        $this->assertTrue($progress['survey']['available']);

        // The theme still needs the checklist answered, then it completes.
        $this->assertFalse($progress['theme_completed']);
        SurveyAnswer::create([
            'custom_form_id' => $theme->custom_form_id,
            'user_id' => 7,
        ]);

        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);

        $this->assertTrue($progress['survey']['completed']);
        $this->assertTrue($progress['theme_completed']);
    }

    private function themeWithExamAndChecklist(): array
    {
        $form = CustomForm::create(['title' => 'Checklist']);
        $theme = LessonTheme::create([
            'title' => 'Exam + Checklist Theme',
            'portfolio' => 0,
            'has_case_study' => 0,
            'custom_form_id' => $form->id,
        ]);
        $material = LessonMaterial::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Basic',
            'priority' => 1,
            'has_understand' => 0,
            'material_type' => '基礎知識',
        ]);
        LessonAnswer::create([
            'material_id' => $material->id,
            'user_id' => 7,
            'status' => 2,
        ]);
        $exam = LessonExam::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Exam',
            'passing_score' => 80,
            'max_attempts' => 1,
        ]);

        return [$theme->fresh(), $exam];
    }

    public function test_a_required_pledge_blocks_completion_until_signed(): void
    {
        $theme = LessonTheme::create([
            'title' => 'Pledge Theme',
            'portfolio' => 0,
            'has_case_study' => 0,
            'pledge' => true,
            'pledge_file_path' => '/lesson_files/pledge.pdf',
        ]);
        $material = LessonMaterial::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Basic',
            'priority' => 1,
            'has_understand' => 0,
            'material_type' => '基礎知識',
        ]);
        LessonAnswer::create([
            'material_id' => $material->id,
            'user_id' => 7,
            'status' => 2,
        ]);

        // Everything else done, but the pledge is unsigned.
        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);
        $this->assertTrue($progress['basic_completed']);
        $this->assertTrue($progress['pledge']['required']);
        $this->assertFalse($progress['pledge']['signed']);
        $this->assertFalse($progress['theme_completed']);

        LessonPledgeSignature::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => 7,
            'file_path' => 'lesson_pledges/'.$theme->id.'/7_x.pdf',
            'signed_at' => now(),
        ]);

        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);

        $this->assertTrue($progress['pledge']['signed']);
        $this->assertTrue($progress['theme_completed']);
    }

    public function test_a_pledge_toggle_without_a_document_is_not_required(): void
    {
        // Defensive: the toggle alone must not permanently block a theme.
        $theme = LessonTheme::create([
            'title' => 'Pledge Without File',
            'portfolio' => 0,
            'has_case_study' => 0,
            'pledge' => true,
            'pledge_file_path' => null,
        ]);

        $progress = app(LearningProgressService::class)->forThemeIdUser($theme->id, 7);

        $this->assertFalse($progress['pledge']['required']);
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
        // Failing does not block completion: the exam only has to be taken.
        $this->assertTrue($progress['theme_completed']);
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
