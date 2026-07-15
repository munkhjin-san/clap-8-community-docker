<?php

namespace App\Services\SalaryIssue;

use App\Models\EvaluationRecord;
use App\Models\LessonTheme;
use App\Models\ProjectGoal;
use App\Models\SalaryIssue;
use App\Services\Goal\GoalScoreService;
use Illuminate\Validation\ValidationException;

/**
 * Gates salary-issue (昇給課題) creation.
 *
 * Rules:
 *  - How many challenges a user may create in a half-year span is decided by the
 *    PREVIOUS half's total goal score: >=480 -> 2, >=360 -> 1, else 0.
 *  - Grade (等級) from the evaluation rank decides which theme axes (軸) are allowed.
 *  - No theme overlap within the same half (already enforced in save_kadai_template).
 */
class SalaryIssueEligibilityService
{
    private const THRESHOLD_TWO = 480;

    private const THRESHOLD_ONE = 360;

    /** 等級 => allowed count per 軸 (>0 means the axis is selectable for that grade). */
    private const GRADE_AXIS = [
        1 => ['自己' => 2, '組織' => 2, '社会' => 2],
        2 => ['自己' => 1, '組織' => 2, '社会' => 2],
        3 => ['自己' => 0, '組織' => 2, '社会' => 2],
        4 => ['自己' => 0, '組織' => 1, '社会' => 2],
        5 => ['自己' => 0, '組織' => 0, '社会' => 2],
        6 => ['自己' => 0, '組織' => 0, '社会' => 2],
    ];

    private const AXES = ['自己', '組織', '社会'];

    public function __construct(private GoalScoreService $scores) {}

    public function previousHalf(int $year, string $whichHalf): array
    {
        return $whichHalf === 'first'
            ? ['year' => $year - 1, 'which_half' => 'second']
            : ['year' => $year, 'which_half' => 'first'];
    }

    /** Number of challenges the user may create this half (0/1/2). */
    public function allowance(int $userId, int $year, string $whichHalf): int
    {
        $prev = $this->previousHalf($year, $whichHalf);
        $total = $this->scores->totalForUserHalf($userId, $prev['year'], $prev['which_half']);

        if ($total >= self::THRESHOLD_TWO) {
            return 2;
        }

        return $total >= self::THRESHOLD_ONE ? 1 : 0;
    }

    /** Challenges already created for this user in this half. */
    public function usedCount(int $userId, int $year, string $whichHalf): int
    {
        return SalaryIssue::where('user_id', $userId)
            ->whereHas('project_goal', function ($q) use ($year, $whichHalf) {
                $q->where('year', $year)->where('which_half', $whichHalf);
            })
            ->count();
    }

    /** Parse the leading 等級 number from a rank string like "３等級2号俸223000". */
    public function gradeFromRank(?string $rank): ?int
    {
        if (blank($rank)) {
            return null;
        }

        $normalized = mb_convert_kana($rank, 'n'); // full-width digits -> half-width

        return preg_match('/(\d+)\s*等級/u', $normalized, $m) ? (int) $m[1] : null;
    }

    /** Axis (自己/組織/社会) encoded at the start of a theme title_full. */
    public function axisFromTitleFull(?string $titleFull): ?string
    {
        foreach (self::AXES as $axis) {
            if (is_string($titleFull) && str_starts_with($titleFull, $axis)) {
                return $axis;
            }
        }

        return null;
    }

    public function axisAllowedForGrade(?int $grade, ?string $axis): bool
    {
        // Fail open when grade or axis can't be determined (data incomplete).
        if ($grade === null || $axis === null || ! isset(self::GRADE_AXIS[$grade])) {
            return true;
        }

        return (self::GRADE_AXIS[$grade][$axis] ?? 0) > 0;
    }

    /** Map a theme title_full ("軸×テーマ【タイトル】") to a salary-issue-target lesson_theme id. */
    public function resolveThemeId(?string $titleFull): ?int
    {
        if (! is_string($titleFull) || ! preg_match('/【(.+?)】/u', $titleFull, $m)) {
            return null;
        }

        return LessonTheme::salaryIssueTarget()
            ->where('title', $m[1])
            ->value('id');
    }

    public function gradeForUserHalf(int $userId, int $year, string $whichHalf): ?int
    {
        $rank = EvaluationRecord::where('user_id', $userId)
            ->where('year', $year)
            ->where('which_half', $whichHalf)
            ->value('after_salary_rank');

        return $this->gradeFromRank($rank);
    }

    /**
     * Throws ValidationException when the user may not create this challenge.
     * (Theme-overlap is checked separately in save_kadai_template.)
     */
    public function assertCanCreate(ProjectGoal $goal, string $titleFull): void
    {
        $userId = (int) $goal->user_id;
        $year = (int) $goal->year;
        $whichHalf = (string) $goal->which_half;

        $allowance = $this->allowance($userId, $year, $whichHalf);

        if ($allowance <= 0) {
            throw ValidationException::withMessages([
                'message' => '前期の成果目標評価が基準（360点）に達していないため、今期は昇給課題を設定できません。',
            ]);
        }

        if ($this->usedCount($userId, $year, $whichHalf) >= $allowance) {
            throw ValidationException::withMessages([
                'message' => "今期に設定できる昇給課題の上限（{$allowance}件）に達しています。",
            ]);
        }

        $grade = $this->gradeForUserHalf($userId, $year, $whichHalf);
        $axis = $this->axisFromTitleFull($titleFull);

        if (! $this->axisAllowedForGrade($grade, $axis)) {
            throw ValidationException::withMessages([
                'message' => "現在の等級では「{$axis}」の昇給課題テーマを選択できません。",
            ]);
        }
    }

    /** Current half-year span, matching the learner dashboard's convention. */
    public function currentSpan(): array
    {
        $now = now();
        $inFirst = $now->month >= 4 && $now->month <= 9; // Apr–Sep

        return $inFirst
            ? ['year' => $now->year, 'which_half' => 'first']
            : ['year' => $now->year - 1, 'which_half' => 'second'];
    }

    /**
     * Span/theme-level reason a user may NOT challenge this theme in a half.
     * Independent of the specific goal (all goals in a half share these inputs).
     * Null = eligible.
     */
    public function spanChallengeReason(int $userId, int $year, string $whichHalf, LessonTheme $theme): ?string
    {
        if ((int) ($theme->salary_issue_target ?? 0) !== 1) {
            return 'このテーマは昇給課題の対象ではありません。';
        }

        $allowance = $this->allowance($userId, $year, $whichHalf);
        if ($allowance <= 0) {
            return '前期の成果目標評価が基準（360点）に達していないため、今期は昇給課題を設定できません。';
        }

        if ($this->usedCount($userId, $year, $whichHalf) >= $allowance) {
            return "今期に設定できる昇給課題の上限（{$allowance}件）に達しています。";
        }

        $grade = $this->gradeForUserHalf($userId, $year, $whichHalf);
        if (! $this->axisAllowedForGrade($grade, $theme->axis)) {
            return "現在の等級では「{$theme->axis}」の昇給課題テーマを選択できません。";
        }

        $overlap = SalaryIssue::where('user_id', $userId)
            ->where('lesson_theme_id', $theme->id)
            ->whereHas('project_goal', function ($q) use ($year, $whichHalf) {
                $q->where('year', $year)->where('which_half', $whichHalf);
            })
            ->exists();
        if ($overlap) {
            return 'このテーマでは既に今期の昇給課題が設定されています。';
        }

        return null;
    }

    public function themeChallengeReason(ProjectGoal $goal, LessonTheme $theme): ?string
    {
        return $this->spanChallengeReason((int) $goal->user_id, (int) $goal->year, (string) $goal->which_half, $theme);
    }

    public function assertCanChallengeTheme(ProjectGoal $goal, LessonTheme $theme): void
    {
        $reason = $this->themeChallengeReason($goal, $theme);
        if ($reason) {
            throw ValidationException::withMessages(['message' => $reason]);
        }
    }

    /**
     * Options for the "challenge from inside the theme" screen. Eligibility is a
     * single span-level verdict; goals are limited to the CURRENT half-year span
     * (a challenge can only attach to a current-span goal).
     */
    public function themeChallengeOptions(LessonTheme $theme, int $userId): array
    {
        $span = $this->currentSpan();
        $reason = $this->spanChallengeReason($userId, $span['year'], $span['which_half'], $theme);

        $goals = [];
        if ($reason === null) {
            $goals = ProjectGoal::where('user_id', $userId)
                ->where('year', $span['year'])
                ->where('which_half', $span['which_half'])
                ->orderByDesc('id')
                ->get(['id', 'title', 'outcome_goal', 'start_date', 'end_date'])
                ->map(fn (ProjectGoal $goal) => [
                    'goal_id' => $goal->id,
                    'title' => $goal->title ?: $goal->outcome_goal,
                    'start_date' => $goal->start_date,
                    'end_date' => $goal->end_date,
                ])
                ->values()
                ->all();
        }

        return [
            'theme_axis' => $theme->axis,
            'salary_target' => (int) ($theme->salary_issue_target ?? 0) === 1,
            'eligible' => $reason === null,
            'reason' => $reason,
            'span' => $span,
            'goals' => $goals,
        ];
    }

    /** Data for the salary-issue creation screen (allowance / remaining / allowed axes). */
    public function summary(int $userId, int $year, string $whichHalf): array
    {
        $prev = $this->previousHalf($year, $whichHalf);
        $prevTotal = $this->scores->totalForUserHalf($userId, $prev['year'], $prev['which_half']);
        $allowance = $prevTotal >= self::THRESHOLD_TWO ? 2 : ($prevTotal >= self::THRESHOLD_ONE ? 1 : 0);
        $used = $this->usedCount($userId, $year, $whichHalf);
        $grade = $this->gradeForUserHalf($userId, $year, $whichHalf);

        $allowedAxes = collect(self::AXES)
            ->filter(fn ($axis) => $this->axisAllowedForGrade($grade, $axis))
            ->values()
            ->all();

        return [
            'previous_total' => $prevTotal,
            'allowance' => $allowance,
            'used' => $used,
            'remaining' => max($allowance - $used, 0),
            'grade' => $grade,
            'allowed_axes' => $allowedAxes,
        ];
    }
}
