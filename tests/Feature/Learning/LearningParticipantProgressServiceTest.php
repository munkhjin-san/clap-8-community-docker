<?php

namespace Tests\Feature\Learning;

use App\Models\LessonAnswer;
use App\Models\LessonExam;
use App\Models\LessonExamAttempt;
use App\Models\LessonForm;
use App\Models\LessonMaterial;
use App\Models\LessonPortfolio;
use App\Models\LessonSection;
use App\Models\LessonTheme;
use App\Models\User;
use App\Services\Learning\LearningParticipantProgressService;

class LearningParticipantProgressServiceTest extends LearningDatabaseTestCase
{
    public function test_case_study_rows_return_normalized_progress_and_admin_answer_details(): void
    {
        $user = User::create([
            'name' => 'Learner One',
            'email' => 'learner@example.test',
            'password' => 'secret',
        ]);
        $theme = LessonTheme::create([
            'title' => 'Case Theme',
            'portfolio' => 0,
            'has_case_study' => 1,
        ]);
        $basicMaterial = LessonMaterial::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Basic',
            'priority' => 1,
            'has_understand' => 0,
            'material_type' => '基礎知識',
        ]);
        $caseMaterial = LessonMaterial::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Case',
            'priority' => 1,
            'has_understand' => 0,
            'material_type' => 'ケーススタディ',
        ]);
        $exam = LessonExam::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Exam',
            'passing_score' => 80,
            'max_attempts' => 2,
        ]);

        LessonAnswer::create([
            'material_id' => $basicMaterial->id,
            'user_id' => $user->id,
            'status' => 2,
            'cant_understand' => 'No',
            'reason_dnt_und' => 'Clear enough',
        ]);
        LessonAnswer::create([
            'material_id' => $caseMaterial->id,
            'user_id' => $user->id,
            'status' => 2,
            'answer' => 'Case answer',
        ]);
        LessonExamAttempt::create([
            'lesson_exam_id' => $exam->id,
            'user_id' => $user->id,
            'score' => 90,
            'attempt_number' => 1,
            'status' => 'passed',
        ]);

        $rows = app(LearningParticipantProgressService::class)->caseStudyRows($theme->id);
        $row = $rows[$user->id];

        $this->assertSame('Learner One', $row['user']->name);
        $this->assertSame('No', $row['cant_understand']);
        $this->assertSame('Clear enough', $row['reason_dnt_und']);
        $this->assertSame([['title' => 'Case', 'answer' => 'Case answer']], $row['answers']);
        $this->assertTrue($row['progress']['basic_completed']);
        $this->assertTrue($row['progress']['case_completed']);
        $this->assertTrue($row['progress']['exam_passed']);
        $this->assertSame(1, $row['progress']['exam']['attempts_count']);
    }

    public function test_portfolio_rows_return_portfolio_relations_and_normalized_progress(): void
    {
        $user = User::create([
            'name' => 'Portfolio Learner',
            'email' => 'portfolio@example.test',
            'password' => 'secret',
        ]);
        $theme = LessonTheme::create([
            'title' => 'Portfolio Theme',
            'portfolio' => 1,
            'has_case_study' => 0,
        ]);
        $material = LessonMaterial::create([
            'lesson_theme_id' => $theme->id,
            'title' => 'Understanding',
            'priority' => 1,
            'has_understand' => 1,
            'material_type' => '基礎知識',
        ]);
        $portfolio = LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => $user->id,
            'status' => 3,
        ]);

        LessonSection::create([
            'portfolio_id' => $portfolio->id,
            'material_id' => $material->id,
            'user_id' => $user->id,
            'status' => 2,
            'content' => 'Understood',
        ]);
        LessonForm::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => $user->id,
            'question1' => 'Q1',
            'answer1' => 'A1',
            'content' => 'Survey note',
        ]);

        $rows = app(LearningParticipantProgressService::class)->portfolioRows($theme->id);
        $row = $rows->first();

        $this->assertSame('Portfolio Learner', $row->user->name);
        $this->assertSame('A1', $row->lesson_form->answer1);
        $this->assertCount(1, $row->lesson_sections);
        $this->assertSame('Understood', $row->lesson_sections->first()->content);
        // portfolioRows now returns every attempt with its own status (multi-attempt/multi-path);
        // per-attempt status is authoritative rather than a shared per-user progress object.
        $this->assertSame(3, (int) $row->status);
    }
}
