<?php

use App\Models\shiftType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds a 特別休暇 (special_holiday) shift-type record where one doesn't already
 * exist. Production already has it (id 27); this environment (and fresh installs)
 * lacked it, so the special_holiday category — used by the attendance 特別休暇
 * column and the general_position quota in ShiftService — had no record locally.
 *
 * Guarded: if any non-deleted special_holiday record already exists (e.g. prod's
 * id 27, classified by 2026_06_29_000001), this is a no-op.
 */
return new class extends Migration
{
    private const SLUG = 'glowd';

    public function up(): void
    {
        $communityId = DB::table('communities')->where('slug', self::SLUG)->value('id');
        if (!$communityId) {
            return;
        }

        $exists = DB::table('shift_types')
            ->where('category', shiftType::CATEGORY_SPECIAL_HOLIDAY)
            ->where('deleted_flag', 0)
            ->exists();
        if ($exists) {
            return; // already present (e.g. production id 27)
        }

        $now = now();
        $id = DB::table('shift_types')->insertGetId([
            'name' => '特別休暇',
            'abbreviation' => '特別休暇',
            'full_day' => 2,
            'value' => 480,
            'category' => shiftType::CATEGORY_SPECIAL_HOLIDAY,
            'deleted_flag' => 0,
            'community_id' => $communityId,
        ]);

        // Make it selectable for every role (mirrors the id-27 assignment policy).
        $roleIds = DB::table('community_roles')->where('community_id', $communityId)->pluck('id');
        $rows = $roleIds->map(fn ($roleId) => [
            'community_role_id' => $roleId,
            'shift_type_id' => $id,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        if ($rows) {
            DB::table('community_role_shift_type')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        $communityId = DB::table('communities')->where('slug', self::SLUG)->value('id');
        if (!$communityId) {
            return;
        }

        // Only remove an UNREFERENCED special_holiday 特別休暇 record — i.e. the one
        // this migration seeded. Any record with shift_records (e.g. production's
        // in-use id 27) is left untouched.
        $ids = DB::table('shift_types')
            ->where('community_id', $communityId)
            ->where('category', shiftType::CATEGORY_SPECIAL_HOLIDAY)
            ->where('name', '特別休暇')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('shift_records')->whereColumn('shift_records.shift_type', 'shift_types.id');
            })
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('community_role_shift_type')->whereIn('shift_type_id', $ids)->delete();
            DB::table('shift_types')->whereIn('id', $ids)->delete();
        }
    }
};
