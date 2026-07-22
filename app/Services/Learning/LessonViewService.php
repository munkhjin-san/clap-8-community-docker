<?php

namespace App\Services\Learning;

use App\Models\LessonExam;
use App\Models\LessonExamAttempt;
use App\Models\LessonMaterial;
use App\Models\LessonPersonalMaterial;
use App\Models\LessonPortfolio;
use App\Models\LessonTheme;

class LessonViewService
{
    public function materialsQuery($themeId, int $userId, $versionId = null)
    {
        return LessonMaterial::where('lesson_theme_id', $themeId)
            ->when($versionId, fn ($q) => $q->where('lesson_material_version_id', $versionId))
            ->with(['answer' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }])
            ->with(['summaries' => function ($q) use ($userId) {
                $q->with([
                    'questions.answer' => function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    },
                    'answers' => function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    },
                ]);
            }]);
    }

    public function materials($themeId, int $userId, $versionId = null)
    {
        return $this->materialsQuery($themeId, $userId, $versionId)->get();
    }

    // The material version a learner sees = the theme's default version.
    public function defaultVersionId($themeId): ?int
    {
        return \App\Models\LessonMaterialVersion::where('lesson_theme_id', $themeId)
            ->where('is_default', true)
            ->value('id');
    }

    public function material($materialId, int $userId)
    {
        return LessonMaterial::where('id', $materialId)
            ->with(['answer' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }])
            ->with(['summaries' => function ($q) use ($userId) {
                $q->with([
                    'questions.answer' => function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    },
                    'answers' => function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    },
                ]);
            }])
            ->first();
    }

    public function portfolio($themeId, int $userId)
    {
        $portfolio = LessonPortfolio::where('lesson_theme_id', $themeId)
            ->where('user_id', $userId)
            ->currentAttempt()
            ->with('lesson_sections')
            ->first();

        // Path 3 (salary challenge): attach the studied AI material + the chosen
        // group-discussion theme (both live on the attempt's personal material).
        if ($portfolio && $portfolio->salary_issue_id) {
            $personalMaterial = LessonPersonalMaterial::where('lesson_theme_id', $themeId)
                ->where('user_id', $userId)
                ->where('config_key', 'repeater_attempt_'.$portfolio->id)
                ->first();
            $portfolio->setAttribute('ai_material', $personalMaterial?->content);
            $portfolio->setAttribute('discussion_theme', $personalMaterial?->important_point);
        }

        return $portfolio;
    }

    public function examSummary($themeId, int $userId): array
    {
        $exam = LessonExam::where('lesson_theme_id', $themeId)->first();

        if (! $exam) {
            return [
                'exam' => null,
                'attempts' => [],
                'remaining_attempts' => 0,
                'final_attempt_answers' => [],
                'reveal_answers' => false,
            ];
        }

        $attempts = LessonExamAttempt::where('lesson_exam_id', $exam->id)
            ->where('user_id', $userId)
            ->orderByDesc('attempt_number')
            ->get();

        return [
            'exam' => $exam,
            'attempts' => $attempts,
            'remaining_attempts' => max($exam->max_attempts - $attempts->count(), 0),
            'final_attempt_answers' => [],
            'reveal_answers' => false,
        ];
    }

    public function lessonView($themeId, int $userId): array
    {
        return [
            'materials' => $this->materials($themeId, $userId, $this->defaultVersionId($themeId)),
            'portfolio' => $this->portfolio($themeId, $userId),
            'exam' => $this->examSummary($themeId, $userId),
        ];
    }

    /**
     * AI-material context for the theme-open basic view: shown when the learner's
     * CURRENT attempt is attempt_no > 1 (path 2 repeater OR path 3 salary challenge)
     * and they have a prior completed portfolio to build on. No previous_version.
     */
    public function previousExperience(int $themeId, int $userId): array
    {
        $empty = [
            'has_experience' => false,
            'theme' => null,
            'portfolio' => null,
            'personal_material' => null,
            'can_generate_personal_material' => false,
            'is_salary_challenge' => false,
        ];

        $theme = LessonTheme::query()->select('id', 'title')->find($themeId);
        if (! $theme) {
            return $empty;
        }

        $current = LessonPortfolio::where('lesson_theme_id', $themeId)
            ->where('user_id', $userId)
            ->currentAttempt()
            ->first();

        // attempt >1 = AI-generated path: plain repeater (path 2) or salary challenge (path 3).
        $isAiAttempt = $current && (int) $current->attempt_no > 1;
        if (! $isAiAttempt) {
            return $empty;
        }

        // Latest prior completed portfolio (shown as the "previous experience").
        $prior = LessonPortfolio::where('lesson_theme_id', $themeId)
            ->where('user_id', $userId)
            ->where('id', '!=', $current->id)
            ->where('status', 3)
            ->orderByDesc('attempt_no')
            ->orderByDesc('id')
            ->first();

        if (! $prior || ! $this->portfolioHasExperience($prior)) {
            return $empty;
        }

        $personalMaterial = LessonPersonalMaterial::query()
            ->where('lesson_theme_id', $themeId)
            ->where('user_id', $userId)
            ->where('config_key', 'repeater_attempt_'.$current->id)
            ->first();

        return [
            'has_experience' => true,
            'theme' => [
                'id' => $theme->id,
                'title' => $theme->title,
            ],
            'portfolio' => $prior,
            'personal_material' => $personalMaterial,
            'can_generate_personal_material' => true,
            'is_salary_challenge' => (bool) $current->salary_issue_id,
        ];
    }

    private function portfolioHasExperience(LessonPortfolio $portfolio): bool
    {
        return filled($portfolio->public_content);
    }
}
