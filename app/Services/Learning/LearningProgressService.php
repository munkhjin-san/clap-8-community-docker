<?php

namespace App\Services\Learning;

use App\Models\LessonExam;
use App\Models\LessonExamAttempt;
use App\Models\LessonPersonalMaterial;
use App\Models\LessonPortfolio;
use App\Models\LessonTheme;
use Illuminate\Support\Collection;

class LearningProgressService
{
    private const MATERIAL_TYPE_BASIC = '基礎知識';

    private const MATERIAL_TYPE_CASE_STUDY = 'ケーススタディ';

    private const MATERIAL_PRIORITY_SECTION = 1;

    private const ANSWER_STATUS_NOT_UNDERSTOOD = -1;

    private const ANSWER_STATUS_COMPLETED = 2;

    private const SECTION_STATUS_COMPLETED = 2;

    private const PORTFOLIO_DISCUSSION_DRAFT_READY = 1;

    private const PORTFOLIO_DISCUSSION_COMPLETED = 2;

    private const PORTFOLIO_FINAL_COMPLETED = 3;

    private const EXAM_STATUS_PASSED = 'passed';

    public function forThemeUser(LessonTheme $theme, int $userId): array
    {
        $materials = $theme->relationLoaded('materials')
            ? $theme->materials
            : $theme->materials()->with(['answer' => fn ($q) => $q->where('user_id', $userId)])->get();

        $portfolio = $this->portfolio($theme->id, $userId);

        return $this->buildProgress($theme, $userId, $materials, $portfolio);
    }

    public function forThemeUsers(int $themeId, iterable $userIds): array
    {
        $userIds = collect($userIds)
            ->filter()
            ->map(fn ($userId) => (int) $userId)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return [];
        }

        $theme = LessonTheme::with([
            'materials' => fn ($q) => $q->with(['answers' => fn ($q) => $q->whereIn('user_id', $userIds)]),
            'form.survey_answers' => fn ($q) => $q->whereIn('user_id', $userIds),
        ])->findOrFail($themeId);

        // Ordered ascending so keyBy keeps each user's latest attempt (last wins).
        $portfolios = LessonPortfolio::where('lesson_theme_id', $themeId)
            ->whereIn('user_id', $userIds)
            ->orderBy('attempt_no')
            ->orderBy('id')
            ->with('lesson_sections')
            ->get()
            ->keyBy('user_id');

        $exam = LessonExam::where('lesson_theme_id', $themeId)
            ->with(['attempts' => fn ($q) => $q->whereIn('user_id', $userIds)->orderByDesc('attempt_number')])
            ->first();

        return $userIds
            ->mapWithKeys(function (int $userId) use ($theme, $portfolios, $exam) {
                return [
                    $userId => $this->buildProgress(
                        $theme,
                        $userId,
                        $theme->materials,
                        $portfolios->get($userId),
                        $this->examProgress($theme->id, $userId, $exam, true)
                    ),
                ];
            })
            ->all();
    }

    private function buildProgress(
        LessonTheme $theme,
        int $userId,
        Collection $materials,
        ?LessonPortfolio $portfolio,
        ?array $examProgress = null
    ): array {
        $sectionStatuses = $portfolio?->lesson_sections ?? collect();

        $basicMaterials = $materials->filter(function ($material) {
            return $material->material_type === self::MATERIAL_TYPE_BASIC
                && (int) $material->priority === self::MATERIAL_PRIORITY_SECTION
                && ! $this->enabled($material->has_understand);
        });

        $understandingMaterials = $materials->filter(function ($material) {
            return (int) $material->priority === self::MATERIAL_PRIORITY_SECTION
                && $this->enabled($material->has_understand);
        });

        $caseStudyMaterials = $materials->filter(fn ($material) => $material->material_type === self::MATERIAL_TYPE_CASE_STUDY);

        $recurringMaterial = $this->recurringPersonalMaterial($theme, $userId);
        $recurringBasicCompleted = (bool) $recurringMaterial?->understand;
        $recurringBasicNotUnderstood = $recurringMaterial && $recurringMaterial->understand === false;

        $basicCompleted = $recurringBasicCompleted || (
            $this->answersCompleted($basicMaterials, $userId)
            && $this->sectionsCompleted($understandingMaterials, $sectionStatuses)
        );
        $caseCompleted = $this->caseStudiesCompleted($caseStudyMaterials, $sectionStatuses, $userId);
        $exam = $examProgress ?? $this->examProgress($theme->id, $userId);
        $survey = $this->surveyProgress($theme, $userId, $basicCompleted, $caseCompleted, $exam);
        $portfolioProgress = $this->portfolioProgress($theme, $portfolio);

        $themeCompleted = $this->themeCompleted(
            $theme,
            $basicCompleted,
            $caseCompleted,
            $exam,
            $survey,
            $portfolioProgress
        );

        return [
            'basic' => [
                'total' => $recurringMaterial ? 1 : $basicMaterials->count() + $understandingMaterials->count(),
                'answer_total' => $basicMaterials->count(),
                'understanding_total' => $understandingMaterials->count(),
                'completed' => $basicCompleted,
                'not_understood' => $recurringBasicNotUnderstood || $basicMaterials->contains(function ($material) use ($userId) {
                    return (int) ($this->answerForUser($material, $userId)?->status ?? 0) === self::ANSWER_STATUS_NOT_UNDERSTOOD;
                }),
            ],
            'case_study' => [
                'total' => $caseStudyMaterials->count(),
                'completed' => $caseCompleted,
            ],
            'exam' => $exam,
            'survey' => $survey,
            'portfolio' => $portfolioProgress,
            'theme_completed' => $themeCompleted,
            'basic_completed' => $basicCompleted,
            'case_completed' => $caseCompleted,
            'exam_available' => $exam['available'],
            'exam_passed' => $exam['passed'],
            'exam_exhausted' => $exam['exhausted'],
            'survey_available' => $survey['available'],
            'survey_completed' => $survey['completed'],
            'portfolio_required' => $portfolioProgress['required'],
            'portfolio_ready' => $portfolioProgress['draft_ready'],
        ];
    }

    public function forThemeIdUser(int $themeId, int $userId): array
    {
        $theme = LessonTheme::with([
            'materials' => fn ($q) => $q->with(['answer' => fn ($q) => $q->where('user_id', $userId)]),
            'form.survey_answers',
        ])->findOrFail($themeId);

        return $this->forThemeUser($theme, $userId);
    }

    private function portfolio(int $themeId, int $userId)
    {
        return LessonPortfolio::where('lesson_theme_id', $themeId)
            ->where('user_id', $userId)
            ->currentAttempt()
            ->with('lesson_sections')
            ->first();
    }

    private function recurringPersonalMaterial(LessonTheme $theme, int $userId): ?LessonPersonalMaterial
    {
        if (empty($theme->previous_version)) {
            return null;
        }

        return LessonPersonalMaterial::query()
            ->where('lesson_theme_id', $theme->id)
            ->where('user_id', $userId)
            ->where('config_key', 'portfolio_recurring_trainee')
            ->first();
    }

    private function answersCompleted($materials, ?int $userId = null): bool
    {
        if ($materials->isEmpty()) {
            return true;
        }

        return $materials->every(function ($material) use ($userId) {
            return (int) ($this->answerForUser($material, $userId)?->status ?? 0) === self::ANSWER_STATUS_COMPLETED;
        });
    }

    private function sectionsCompleted($materials, $sectionStatuses): bool
    {
        if ($materials->isEmpty()) {
            return true;
        }

        return $materials->every(function ($material) use ($sectionStatuses) {
            $section = $sectionStatuses->firstWhere('material_id', $material->id);

            return (int) ($section?->status ?? 0) === self::SECTION_STATUS_COMPLETED;
        });
    }

    private function caseStudiesCompleted($materials, $sectionStatuses, int $userId): bool
    {
        if ($materials->isEmpty()) {
            return true;
        }

        return $materials->every(function ($material) use ($sectionStatuses, $userId) {
            if ($this->enabled($material->has_understand)) {
                $section = $sectionStatuses->firstWhere('material_id', $material->id);

                return (int) ($section?->status ?? 0) === self::SECTION_STATUS_COMPLETED;
            }

            return (int) ($this->answerForUser($material, $userId)?->status ?? 0) === self::ANSWER_STATUS_COMPLETED;
        });
    }

    private function examProgress(int $themeId, int $userId, ?LessonExam $loadedExam = null, bool $examWasPreloaded = false): array
    {
        $exam = $examWasPreloaded ? $loadedExam : LessonExam::where('lesson_theme_id', $themeId)->first();

        if (! $exam) {
            return [
                'available' => false,
                'passed' => false,
                'exhausted' => false,
                'attempts_count' => 0,
                'remaining_attempts' => 0,
                'latest_score' => null,
                'latest_status' => null,
            ];
        }

        $attempts = $exam->relationLoaded('attempts')
            ? $exam->attempts->where('user_id', $userId)->sortByDesc('attempt_number')->values()
            : LessonExamAttempt::where('lesson_exam_id', $exam->id)
                ->where('user_id', $userId)
                ->orderByDesc('attempt_number')
                ->get();
        $latestAttempt = $attempts->first();

        return [
            'available' => true,
            'passed' => $attempts->contains(fn ($attempt) => $attempt->status === self::EXAM_STATUS_PASSED),
            'exhausted' => $attempts->count() >= (int) $exam->max_attempts,
            'attempts_count' => $attempts->count(),
            'remaining_attempts' => max((int) $exam->max_attempts - $attempts->count(), 0),
            'latest_score' => $latestAttempt?->score,
            'latest_status' => $latestAttempt?->status,
        ];
    }

    private function surveyProgress(LessonTheme $theme, int $userId, bool $basicCompleted, bool $caseCompleted, array $exam): array
    {
        $available = ! empty($theme->custom_form_id)
            && $basicCompleted
            && $caseCompleted
            && (! $exam['available'] || $exam['passed']);

        $completedAnswer = $theme->form?->survey_answers?->firstWhere('user_id', $userId);

        return [
            'available' => $available,
            'completed' => (bool) $completedAnswer,
            'completed_at' => $completedAnswer?->updated_at,
        ];
    }

    private function portfolioProgress(LessonTheme $theme, $portfolio): array
    {
        $status = (int) ($portfolio?->status ?? 0);

        return [
            'required' => $this->enabled($theme->portfolio),
            'status' => $status,
            'draft_ready' => $status >= self::PORTFOLIO_DISCUSSION_DRAFT_READY,
            'discussion_completed' => $status >= self::PORTFOLIO_DISCUSSION_COMPLETED,
            'completed' => $status >= self::PORTFOLIO_FINAL_COMPLETED,
        ];
    }

    private function themeCompleted(
        LessonTheme $theme,
        bool $basicCompleted,
        bool $caseCompleted,
        array $exam,
        array $survey,
        array $portfolio
    ): bool {
        if (! $basicCompleted || ! $caseCompleted) {
            return false;
        }

        if ($exam['available'] && ! $exam['passed']) {
            return false;
        }

        if (! empty($theme->custom_form_id) && ! $survey['completed']) {
            return false;
        }

        if ($portfolio['required']) {
            return $portfolio['completed'];
        }

        return true;
    }

    private function enabled($value): bool
    {
        return $value === true || (int) $value === 1;
    }

    private function answerForUser($material, ?int $userId)
    {
        if ($material->relationLoaded('answer')) {
            return $material->answer;
        }

        if ($userId !== null && $material->relationLoaded('answers')) {
            return $material->answers->firstWhere('user_id', $userId);
        }

        if ($userId !== null) {
            return $material->answer()->where('user_id', $userId)->first();
        }

        return null;
    }
}
