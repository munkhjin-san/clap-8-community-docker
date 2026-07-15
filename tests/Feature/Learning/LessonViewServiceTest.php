<?php

namespace Tests\Feature\Learning;

use App\Models\LessonAnswer;
use App\Models\LessonExam;
use App\Models\LessonExamAttempt;
use App\Models\LessonMaterial;
use App\Models\LessonPortfolio;
use App\Models\LessonSection;
use App\Models\LessonSummary;
use App\Models\LessonSummaryAnswer;
use App\Models\LessonSummaryQuestion;
use App\Models\LessonTheme;
use App\Services\Learning\LessonViewService;

class LessonViewServiceTest extends LearningDatabaseTestCase
{
    public function test_lesson_view_loads_user_scoped_materials_portfolio_and_exam_summary(): void
    {
        $theme = LessonTheme::create(['title' => 'Theme']);
        $material = LessonMaterial::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Material',
            'priority' => 1,
            'material_type' => '基礎知識',
        ]);
        $summary = LessonSummary::create([
            'lesson_material_id' => $material->id,
            'summary_title' => 'Check',
        ]);
        $question = LessonSummaryQuestion::create([
            'lesson_summary_id' => $summary->id,
            'question' => 'Question',
        ]);
        $portfolio = LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => 7,
            'status' => 1,
        ]);
        $exam = LessonExam::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Exam',
            'max_attempts' => 3,
        ]);

        LessonAnswer::create([
            'material_id' => $material->id,
            'user_id' => 7,
            'answer' => 'Mine',
            'status' => 2,
        ]);
        LessonAnswer::create([
            'material_id' => $material->id,
            'user_id' => 99,
            'answer' => 'Other user',
            'status' => 2,
        ]);
        LessonSummaryAnswer::create([
            'lesson_summary_id' => $summary->id,
            'lesson_summary_question_id' => $question->id,
            'user_id' => 7,
            'answer_val' => 'Summary answer',
        ]);
        LessonSection::create([
            'portfolio_id' => $portfolio->id,
            'material_id' => $material->id,
            'user_id' => 7,
            'status' => 2,
        ]);
        LessonExamAttempt::create([
            'lesson_exam_id' => $exam->id,
            'user_id' => 7,
            'score' => 90,
            'attempt_number' => 1,
            'status' => 'passed',
        ]);

        $view = app(LessonViewService::class)->lessonView($theme->id, 7);

        $this->assertCount(1, $view['materials']);
        $this->assertSame('Mine', $view['materials']->first()->answer->answer);
        $this->assertSame(7, $view['materials']->first()->answer->user_id);
        $this->assertSame('Summary answer', $view['materials']->first()->summaries->first()->answers->first()->answer_val);
        $this->assertSame('Summary answer', $view['materials']->first()->summaries->first()->questions->first()->answer->answer_val);
        $this->assertSame(1, $view['portfolio']->status);
        $this->assertCount(1, $view['portfolio']->lesson_sections);
        $this->assertSame($exam->id, $view['exam']['exam']->id);
        $this->assertCount(1, $view['exam']['attempts']);
        $this->assertSame(2, $view['exam']['remaining_attempts']);
        $this->assertFalse($view['exam']['reveal_answers']);
    }

    public function test_previous_experience_returns_prior_portfolio_for_repeater_attempt(): void
    {
        $theme = LessonTheme::create(['title' => 'Theme', 'portfolio' => 1]);
        // Attempt 1 (path 1) completed.
        LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'attempt_no' => 1,
            'user_id' => 7,
            'status' => 3,
            'public_title' => 'Previous title',
            'public_content' => 'Previous public portfolio',
        ]);
        // Attempt 2 (path 2, in progress) = current repeater attempt.
        LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'attempt_no' => 2,
            'user_id' => 7,
            'status' => 0,
        ]);

        $payload = app(LessonViewService::class)->previousExperience($theme->id, 7);

        $this->assertTrue($payload['has_experience']);
        $this->assertSame('Theme', $payload['theme']['title']);
        $this->assertSame('Previous title', $payload['portfolio']->public_title);
        $this->assertSame('Previous public portfolio', $payload['portfolio']->public_content);
    }

    public function test_previous_experience_returns_false_on_first_attempt(): void
    {
        $theme = LessonTheme::create(['title' => 'Theme', 'portfolio' => 1]);
        // Only attempt 1 (path 1) — even if completed, not a repeater yet.
        LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'attempt_no' => 1,
            'user_id' => 7,
            'status' => 3,
            'public_content' => 'First portfolio',
        ]);

        $payload = app(LessonViewService::class)->previousExperience($theme->id, 7);

        $this->assertFalse($payload['has_experience']);
        $this->assertNull($payload['portfolio']);
    }

    public function test_previous_experience_returns_false_when_no_prior_completed_portfolio(): void
    {
        $theme = LessonTheme::create(['title' => 'Theme', 'portfolio' => 1]);
        // Repeater attempt exists but no prior COMPLETED (status 3) portfolio.
        LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'attempt_no' => 1,
            'user_id' => 7,
            'status' => 0,
        ]);
        LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'attempt_no' => 2,
            'user_id' => 7,
            'status' => 0,
        ]);

        $payload = app(LessonViewService::class)->previousExperience($theme->id, 7);

        $this->assertFalse($payload['has_experience']);
        $this->assertNull($payload['portfolio']);
    }
}
