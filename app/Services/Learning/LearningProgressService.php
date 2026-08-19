<?php

namespace App\Services\Learning;

use App\Models\LessonExam;
use App\Models\LessonExamAttempt;
use App\Models\LessonPersonalMaterial;
use App\Models\LessonPledgeSignature;
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
            ->whereNull('lesson_material_id')
            ->with(['attempts' => fn ($q) => $q->whereIn('user_id', $userIds)->orderByDesc('attempt_number')])
            ->first();

        // Preload every section exam once (with all users' attempts) so the
        // per-user progress build never issues a query-per-user. Skip entirely
        // when no material has its own exam.
        $examMaterialIds = $theme->materials
            ->filter(fn ($material) => $this->enabled($material->has_exam))
            ->pluck('id')
            ->filter();
        $materialExams = $examMaterialIds->isEmpty()
            ? collect()
            : LessonExam::whereIn('lesson_material_id', $examMaterialIds->all())
                ->with(['attempts' => fn ($q) => $q->whereIn('user_id', $userIds)->orderByDesc('attempt_number')])
                ->get()
                ->keyBy('lesson_material_id');

        // One lookup for everyone: pledgeProgress would otherwise query per user.
        $pledgeSignatures = ($this->enabled($theme->pledge) && filled($theme->pledge_file_path))
            ? LessonPledgeSignature::where('lesson_theme_id', $themeId)
                ->whereIn('user_id', $userIds)
                ->get()
                ->keyBy('user_id')
            : collect();

        return $userIds
            ->mapWithKeys(function (int $userId) use ($theme, $portfolios, $exam, $materialExams, $pledgeSignatures) {
                return [
                    $userId => $this->buildProgress(
                        $theme,
                        $userId,
                        $theme->materials,
                        $portfolios->get($userId),
                        $this->examProgress($theme->id, $userId, $exam, true),
                        $materialExams,
                        $pledgeSignatures
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
        ?array $examProgress = null,
        ?Collection $materialExams = null,
        ?Collection $pledgeSignatures = null
    ): array {
        $sectionStatuses = $portfolio?->lesson_sections ?? collect();

        // Answer-based sections: completion is recorded on the material's answer
        // (plain 基礎知識 sections with no 理解度 check and no exam).
        $basicMaterials = $materials->filter(function ($material) {
            return $material->material_type === self::MATERIAL_TYPE_BASIC
                && (int) $material->priority === self::MATERIAL_PRIORITY_SECTION
                && ! $this->enabled($material->has_understand)
                && ! $this->enabled($material->has_exam);
        });

        // Section-status-based sections: completion is recorded in lesson_sections.
        // This covers 理解度チェック (has_understand) sections and exam sections
        // (has_exam) — both are marked complete via updateSection, not the answer.
        $understandingMaterials = $materials->filter(function ($material) {
            return (int) $material->priority === self::MATERIAL_PRIORITY_SECTION
                && ($this->enabled($material->has_understand) || $this->enabled($material->has_exam));
        });

        $caseStudyMaterials = $materials->filter(fn ($material) => $material->material_type === self::MATERIAL_TYPE_CASE_STUDY);

        $recurringMaterial = $this->recurringPersonalMaterial($theme, $userId, $portfolio);
        $recurringBasicCompleted = (bool) $recurringMaterial?->understand;
        $recurringBasicNotUnderstood = $recurringMaterial && $recurringMaterial->understand === false;

        // A finished portfolio proves the basic stage was cleared: the portfolio
        // step is unreachable until every section is complete. Older completions
        // (pre per-section records) have no lesson_sections rows at all, so
        // without this they would report 知識研修 as unfinished forever.
        $portfolioFinished = (int) ($portfolio?->status ?? 0) >= self::PORTFOLIO_FINAL_COMPLETED;

        $basicCompleted = $recurringBasicCompleted || $portfolioFinished || (
            $this->answersCompleted($basicMaterials, $userId)
            && $this->sectionsCompleted($understandingMaterials, $sectionStatuses)
        );
        $caseCompleted = $this->caseStudiesCompleted($caseStudyMaterials, $sectionStatuses, $userId);
        $exam = $examProgress ?? $this->examProgress($theme->id, $userId);
        $survey = $this->surveyProgress($theme, $userId, $basicCompleted, $caseCompleted, $exam);
        $pledge = $this->pledgeProgress($theme, $userId, $pledgeSignatures);
        $portfolioProgress = $this->portfolioProgress($theme, $portfolio);

        $themeCompleted = $this->themeCompleted(
            $theme,
            $basicCompleted,
            $caseCompleted,
            $exam,
            $survey,
            $pledge,
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
            'material_exams' => $this->materialExamsProgress($materials, $userId, $materialExams),
            'survey' => $survey,
            'pledge' => $pledge,
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

    /**
     * On AI attempts (attempt_no > 1: plain repeater or salary challenge) the
     * 知識研修 stage is the generated 個別研修資料, not the basic sections. Its
     * completion lives on the personal material for THIS attempt, keyed
     * `repeater_attempt_{portfolioId}` (see LessonController::repeaterConfigKey).
     */
    private function recurringPersonalMaterial(
        LessonTheme $theme,
        int $userId,
        ?LessonPortfolio $portfolio
    ): ?LessonPersonalMaterial {
        if (! $portfolio || (int) $portfolio->attempt_no <= 1) {
            return null;
        }

        return LessonPersonalMaterial::query()
            ->where('lesson_theme_id', $theme->id)
            ->where('user_id', $userId)
            ->where('config_key', 'repeater_attempt_'.$portfolio->id)
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
        // Theme exam only: a row with lesson_material_id set is a per-section
        // exam and must not be treated as the theme's exam.
        $exam = $examWasPreloaded
            ? $loadedExam
            : LessonExam::where('lesson_theme_id', $themeId)->whereNull('lesson_material_id')->first();

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

    /**
     * Per-section exam progress, keyed by material id. Only materials that have
     * their own exam (lesson_material_id) appear; the theme exam is handled
     * separately by examProgress().
     */
    private function materialExamsProgress(Collection $materials, int $userId, ?Collection $preloadedExams = null): array
    {
        // Only materials that carry their own exam (has_exam) are relevant; this
        // also keeps the fallback query from running when none do.
        $materialIds = $materials
            ->filter(fn ($material) => $this->enabled($material->has_exam))
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($materialIds->isEmpty()) {
            return [];
        }

        $exams = $preloadedExams ?? LessonExam::whereIn('lesson_material_id', $materialIds->all())
            ->with(['attempts' => fn ($q) => $q->where('user_id', $userId)->orderByDesc('attempt_number')])
            ->get()
            ->keyBy('lesson_material_id');

        return $materialIds
            ->mapWithKeys(function (int $materialId) use ($exams, $userId) {
                $exam = $exams->get($materialId);

                if (! $exam) {
                    return [];
                }

                // Filter to this user (preloaded exams carry every user's attempts).
                $attempts = $exam->attempts
                    ->where('user_id', $userId)
                    ->sortByDesc('attempt_number')
                    ->values();
                $latestAttempt = $attempts->first();

                return [
                    $materialId => [
                        'available' => true,
                        'passed' => $attempts->contains(fn ($attempt) => $attempt->status === self::EXAM_STATUS_PASSED),
                        'exhausted' => $attempts->count() >= (int) $exam->max_attempts,
                        'attempts_count' => $attempts->count(),
                        'remaining_attempts' => max((int) $exam->max_attempts - $attempts->count(), 0),
                        'latest_score' => $latestAttempt?->score,
                        'latest_status' => $latestAttempt?->status,
                    ],
                ];
            })
            ->all();
    }

    private function surveyProgress(LessonTheme $theme, int $userId, bool $basicCompleted, bool $caseCompleted, array $exam): array
    {
        $available = ! empty($theme->custom_form_id)
            && $basicCompleted
            && $caseCompleted
            // Taking the exam is what unlocks the checklist; passing is not
            // required, so a failed attempt still lets the learner advance.
            && (! $exam['available'] || $exam['attempts_count'] > 0);

        $completedAnswer = $theme->form?->survey_answers?->firstWhere('user_id', $userId);

        return [
            'available' => $available,
            'completed' => (bool) $completedAnswer,
            'completed_at' => $completedAnswer?->updated_at,
        ];
    }

    /**
     * 誓約書 progress: required when the theme has the toggle on with a document,
     * satisfied once the learner has signed their own copy.
     *
     * @return array{required: bool, signed: bool, signed_at: mixed, file_available: bool}
     */
    private function pledgeProgress(LessonTheme $theme, int $userId, ?Collection $preloaded = null): array
    {
        $required = $this->enabled($theme->pledge) && filled($theme->pledge_file_path);

        if (! $required) {
            return [
                'required' => false,
                'signed' => false,
                'signed_at' => null,
                'signature_id' => null,
                'file_available' => filled($theme->pledge_file_path),
            ];
        }

        $signature = $preloaded !== null
            ? $preloaded->get($userId)
            : LessonPledgeSignature::query()
                ->where('lesson_theme_id', $theme->id)
                ->where('user_id', $userId)
                ->first();

        return [
            'required' => true,
            'signed' => (bool) $signature?->signed_at,
            'signed_at' => $signature?->signed_at,
            // Lets an admin open this learner's signed copy.
            'signature_id' => $signature?->id,
            'file_available' => true,
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
        array $pledge,
        array $portfolio
    ): bool {
        if (! $basicCompleted || ! $caseCompleted) {
            return false;
        }

        // Same rule as the checklist: the exam must have been taken, but a
        // failed attempt does not block the theme from completing.
        if ($exam['available'] && $exam['attempts_count'] < 1) {
            return false;
        }

        if (! empty($theme->custom_form_id) && ! $survey['completed']) {
            return false;
        }

        // 誓約書: the theme cannot finish until the learner has signed it.
        if ($pledge['required'] && ! $pledge['signed']) {
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
