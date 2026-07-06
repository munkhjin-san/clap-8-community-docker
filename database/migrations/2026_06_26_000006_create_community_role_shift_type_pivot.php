<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-role selectable shift types (configurable, multi-community).
 *
 * Replaces ShiftService's position_id-based shift-type filtering with a
 * role->shift_types assignment. Seeds each role to reproduce current behavior.
 * Note: the old general_position (C-G) branch only gated ids [17,27] — 17 is in
 * UNUSED_IDS (always excluded) and 27 does not exist — so it was a no-op and is
 * dropped. work_type and UNUSED_IDS stay as code filters in ShiftService.
 */
return new class extends Migration
{
    private const SLUG = 'glowd';

    public function up(): void
    {
        if (!Schema::hasTable('community_role_shift_type')) {
            Schema::create('community_role_shift_type', function (Blueprint $table) {
                $table->id();
                $table->foreignId('community_role_id')->constrained('community_roles')->cascadeOnDelete();
                $table->foreignId('shift_type_id')->constrained('shift_types')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['community_role_id', 'shift_type_id']);
            });
        }

        $communityId = DB::table('communities')->where('slug', self::SLUG)->value('id');
        if (!$communityId) {
            return;
        }

        $allIds = DB::table('shift_types')->where('deleted_flag', 0)->pluck('id')->map(fn ($i) => (int) $i)->all();

        // Position-level exclusions reproduced from ShiftService (27 is absent, harmless).
        $elseBranch = array_values(array_diff($allIds, [14, 15, 16, 27]));                 // non-privileged positions
        $contract = array_values(array_diff($allIds, [14, 15, 16, 27, 19, 20, 21, 22, 23, 24, 25, 26]));
        $registered = array_values(array_intersect($allIds, [1, 5]));

        $roleSets = [
            'board' => $allIds,
            'pm' => $allIds,
            'regular_employee' => $allIds,
            'project_leader' => $allIds,
            'member' => $allIds,
            'hr' => $allIds,
            'admin' => $allIds,
            'transferred_employee' => $elseBranch,
            'partner' => $elseBranch,
            'contract_employee' => $contract,
            'registered' => $registered,
        ];

        $now = now();
        foreach ($roleSets as $key => $ids) {
            $roleId = DB::table('community_roles')->where('community_id', $communityId)->where('key', $key)->value('id');
            if (!$roleId) {
                continue;
            }
            $rows = [];
            foreach ($ids as $sid) {
                $rows[] = [
                    'community_role_id' => $roleId,
                    'shift_type_id' => $sid,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows) {
                DB::table('community_role_shift_type')->insertOrIgnore($rows);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('community_role_shift_type');
    }
};
