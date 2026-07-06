<?php

use App\Models\shiftType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrects the earlier "shift_type 27 is dead, drop it" assumption — which was
 * made against a test DB that lacks id 27. In PRODUCTION id 27 (特別休暇,
 * full_day=2, value=480) is a live full-day special holiday with its own payroll
 * field (attendance.special_holiday). Migrations 000006/000007 therefore left 27
 * with a NULL category and only assigned it to the "all-ids" roles.
 *
 * This migration:
 *   1. Classifies id 27 under the new `special_holiday` category.
 *   2. Makes 27 assignable to EVERY glowd role (per product decision: bury the
 *      old general_position selectability gate for now; treat 27 as a normal
 *      pivot-assignable shift type — the general_position quota in
 *      ShiftService::remainingSpecialHoliday still governs how many can be booked).
 *
 * Fully guarded: where id 27 does not exist (this test DB) every step is a no-op,
 * so it is safe everywhere and only does real work where 27 is present.
 */
return new class extends Migration
{
    private const SLUG = 'glowd';
    private const SPECIAL_HOLIDAY_ID = 27;

    public function up(): void
    {
        if (!DB::table('shift_types')->where('id', self::SPECIAL_HOLIDAY_ID)->exists()) {
            return; // test DB / installs without the legacy special-holiday row
        }

        DB::table('shift_types')
            ->where('id', self::SPECIAL_HOLIDAY_ID)
            ->update(['category' => shiftType::CATEGORY_SPECIAL_HOLIDAY]);

        $communityId = DB::table('communities')->where('slug', self::SLUG)->value('id');
        if (!$communityId) {
            return;
        }

        $now = now();
        $roleIds = DB::table('community_roles')->where('community_id', $communityId)->pluck('id');
        $rows = $roleIds->map(fn ($roleId) => [
            'community_role_id' => $roleId,
            'shift_type_id' => self::SPECIAL_HOLIDAY_ID,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows) {
            DB::table('community_role_shift_type')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        if (!DB::table('shift_types')->where('id', self::SPECIAL_HOLIDAY_ID)->exists()) {
            return;
        }

        DB::table('community_role_shift_type')->where('shift_type_id', self::SPECIAL_HOLIDAY_ID)->delete();
        DB::table('shift_types')->where('id', self::SPECIAL_HOLIDAY_ID)->update(['category' => null]);
    }
};
