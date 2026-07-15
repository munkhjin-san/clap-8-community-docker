<?php

namespace App\Services\Goal;

use App\Models\ProjectGoal;

/**
 * Server-side reproduction of the goal scoring that lives in the frontend
 * dashboardGoals store (kpiCalculation / overallScore / totalOverallScore).
 * Kept as the authoritative source for salary-issue eligibility gating.
 */
class GoalScoreService
{
    /** general_position => [kpi weight, kgi weight] (percent). Default 50/50. */
    private const WEIGHTS = [
        '一般職' => ['kpi' => 80, 'kgi' => 20],
        'A' => ['kpi' => 70, 'kgi' => 30],
        'B' => ['kpi' => 60, 'kgi' => 40],
        'C' => ['kpi' => 50, 'kgi' => 50],
        'D' => ['kpi' => 40, 'kgi' => 60],
        'E' => ['kpi' => 30, 'kgi' => 70],
        'F' => ['kpi' => 20, 'kgi' => 80],
        'G' => ['kpi' => 10, 'kgi' => 90],
    ];

    public function kpi($steps): int
    {
        $steps = collect($steps);
        if ($steps->isEmpty()) {
            return 0;
        }

        $totalProgress = $steps->sum(fn ($step) => (int) ($step->progress ?? 0));
        $maxProgress = $steps->count() * 100;

        return $maxProgress > 0 ? (int) round(($totalProgress / $maxProgress) * 100) : 0;
    }

    public function overallScore(ProjectGoal $goal): int
    {
        $steps = $goal->relationLoaded('steps') ? $goal->steps : $goal->steps()->get();

        if ($steps->isEmpty()) {
            return (int) ($goal->achievement_rate ?? 0);
        }

        $kpi = $this->kpi($steps);
        $kgi = (int) ($goal->achievement_rate ?? 0);

        $position = $goal->relationLoaded('user') ? ($goal->user->general_position ?? null) : null;
        $weight = self::WEIGHTS[$position] ?? ['kpi' => 50, 'kgi' => 50];

        return (int) round(($kpi * $weight['kpi']) / 100 + ($kgi * $weight['kgi']) / 100);
    }

    /**
     * Sum of overall scores across all of a user's goals in a half-year span.
     */
    public function totalForUserHalf(int $userId, int $year, string $whichHalf): int
    {
        return ProjectGoal::where('user_id', $userId)
            ->where('year', $year)
            ->where('which_half', $whichHalf)
            ->with(['steps', 'user:id,general_position'])
            ->get()
            ->sum(fn (ProjectGoal $goal) => $this->overallScore($goal));
    }
}
