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

        $fullAccessIds = (array) config('access.project_full_access_user_ids', []);
        if (($user->position_id && $user->position_id < 6) || in_array((int) $user->id, $fullAccessIds, true)) {
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

        if (in_array(
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
