<?php

namespace App\Services\Learning;

use App\Models\LessonPortfolio;
use App\Models\LessonSection;
use App\Models\LessonTheme;
use App\Models\SalaryIssue;
use Illuminate\Support\Collection;

/**
 * Learning attempts for the multi-version model: a theme can be learned many
 * times, each learn = one LessonPortfolio (attempt_no). Path is derived:
 *  - attempt_no 1                     -> path 1 (first learner, pre-created content)
 *  - attempt_no > 1, no salary_issue  -> path 2 (repeater, AI-generated)
 *  - salary_issue_id set              -> path 3 (salary challenge)
 */
class LearningAttemptService
{
    private const STATUS_CLEARED = 3;

    public function attempts(int $themeId, int $userId): Collection
    {
        return LessonPortfolio::where('lesson_theme_id', $themeId)
            ->where('user_id', $userId)
            ->orderBy('attempt_no')
            ->orderBy('id')
            ->get();
    }

    public function cleared(int $themeId, int $userId): bool
    {
        return LessonPortfolio::where('lesson_theme_id', $themeId)
            ->where('user_id', $userId)
            ->where('status', self::STATUS_CLEARED)
            ->exists();
    }

    public function currentAttempt(int $themeId, int $userId): ?LessonPortfolio
    {
        return LessonPortfolio::where('lesson_theme_id', $themeId)
            ->where('user_id', $userId)
            ->currentAttempt()
            ->first();
    }

    public function nextAttemptNo(int $themeId, int $userId): int
    {
        return (int) LessonPortfolio::where('lesson_theme_id', $themeId)
            ->where('user_id', $userId)
            ->max('attempt_no') + 1;
    }

    /**
     * Delete an attempt that hasn't cleared its first stage yet (status < 1) —
     * removes the portfolio + its section drafts, and (for a path-3 attempt) the
     * linked salary issue that was being abandoned before any learning.
     */
    public function deleteAttempt(LessonPortfolio $portfolio): void
    {
        LessonSection::where('portfolio_id', $portfolio->id)->delete();

        if ($portfolio->salary_issue_id) {
            SalaryIssue::where('id', $portfolio->salary_issue_id)->delete();
        }

        $portfolio->delete();
    }

    /** Start a new learning attempt (path 2 "learn again"). */
    public function startAttempt(LessonTheme $theme, int $userId): LessonPortfolio
    {
        return LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => $userId,
            'attempt_no' => $this->nextAttemptNo($theme->id, $userId),
            'status' => 0,
        ]);
    }

    /** Theme-open state for the learner: history + which path options to offer. */
    public function state(LessonTheme $theme, int $userId): array
    {
        $attempts = $this->attempts($theme->id, $userId);
        $cleared = $attempts->contains(fn ($p) => (int) $p->status === self::STATUS_CLEARED);
        $current = $attempts->sortByDesc('attempt_no')->first();
        $salaryTarget = (int) ($theme->salary_issue_target ?? 0) === 1;

        return [
            'theme_id' => $theme->id,
            'cleared' => $cleared,
            'attempts' => $attempts->map(fn ($p) => [
                'id' => $p->id,
                'attempt_no' => (int) $p->attempt_no,
                'path' => $p->path,
                'status' => (int) $p->status,
                'title' => $p->public_title ?: $p->portfolio_title,
                'created_at' => $p->created_at,
            ])->values(),
            'current' => $current ? [
                'id' => $current->id,
                'attempt_no' => (int) $current->attempt_no,
                'status' => (int) $current->status,
                'path' => $current->path,
            ] : null,
            // First-timers get path 1 (pre-created content). Once cleared, they can
            // learn again (path 2); path 3 (salary) is offered on target themes but
            // its route is wired later.
            'options' => [
                'path1' => ! $cleared,
                'path2' => $cleared,
                'path3' => $cleared && $salaryTarget,
            ],
        ];
    }
}
