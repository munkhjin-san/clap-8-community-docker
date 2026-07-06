<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Splits the single `special_leave` shift-type category into three distinct
 * categories — condolence(14) / transfer(15) / ODA(16) — because they map to
 * distinct payroll fields (condolence_holiday / special_holiday / oda_holiday)
 * and must be told apart. "Any special leave" checks use shiftType::SPECIAL_LEAVE.
 * Idempotent re-seed of the already-categorized glowd rows.
 */
return new class extends Migration
{
    private const SLUG = 'glowd';

    private const MAP = [
        14 => 'special_leave_condolence',
        15 => 'special_leave_transfer',
        16 => 'special_leave_oda',
    ];

    public function up(): void
    {
        $this->apply(self::MAP);
    }

    public function down(): void
    {
        // collapse back to the single category
        $this->apply([14 => 'special_leave', 15 => 'special_leave', 16 => 'special_leave']);
    }

    private function apply(array $map): void
    {
        $communityId = DB::table('communities')->where('slug', self::SLUG)->value('id');
        if (!$communityId) {
            return;
        }
        foreach ($map as $id => $category) {
            DB::table('shift_types')
                ->where('community_id', $communityId)
                ->where('id', $id)
                ->update(['category' => $category]);
        }
    }
};
