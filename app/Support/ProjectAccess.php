<?php

namespace App\Support;

use App\Models\ProjectRecord;
use App\Models\User;

class ProjectAccess
{
    public static function allows(?User $user, ProjectRecord $project): bool
    {
        if (!$user) {
            return false;
        }

        // Community-aware: boss (役員 / 承認者 / 上位役職) or admin see every project.
        // Env-configured full-access ids stay as an optional break-glass fallback.
        $fullAccessIds = (array) config('access.project_full_access_user_ids', []);
        if ($user->isBoss() || $user->isAdmin() || in_array((int) $user->id, $fullAccessIds, true)) {
            return true;
        }

        $project->loadMissing(['manager', 'members']);

        return $project->manager->contains('id', $user->id)
            || $project->members->contains('id', $user->id)
            || $project->director_id === $user->id;
    }

    public static function canViewActualResultPayroll(?User $user, ProjectRecord $project): bool
    {
        if (! $user) {
            return false;
        }

        // Admin sees payroll; env-configured ids remain an optional break-glass fallback.
        if ($user->isAdmin() || in_array(
            (int) $user->id,
            (array) config('access.actual_result_payroll_user_ids', []),
            true
        )) {
            return true;
        }

        $project->loadMissing('manager');

        return $project->manager->contains('id', $user->id);
    }
}
