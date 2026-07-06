<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a stable, code-facing classification to shift_types so attendance /
 * payroll / paid-leave logic can key off CATEGORY (+ amount) instead of
 * hardcoded ids. Shift-type records stay CRUD-customizable; the category is the
 * system meaning. See docs/shift_type_hardcoding_inventory.md.
 *
 * Seeds glowd's existing 26 shift types to exactly reproduce today's id groups
 * (behavior-preserving — no logic is switched over in this migration).
 */
return new class extends Migration
{
    private const SLUG = 'glowd';

    // glowd shift_type id => [category, hours(amount) | null]
    private const SEED = [
        0  => ['day_off', null],
        1  => ['work', null],
        2  => ['absence', null],
        3  => ['planned_paid_leave', null],
        5  => ['annual_leave_full', null],
        6  => ['annual_leave_half', null],
        7  => ['annual_leave_hourly', 1],
        8  => ['annual_leave_hourly', 2],
        9  => ['annual_leave_hourly', 3],
        10 => ['annual_leave_hourly', 4],
        11 => ['annual_leave_hourly', 5],
        12 => ['annual_leave_hourly', 6],
        13 => ['annual_leave_hourly', 7],
        14 => ['special_leave_condolence', null],
        15 => ['special_leave_transfer', null],
        16 => ['special_leave_oda', null],
        17 => ['comp_holiday', null],
        18 => ['legal_holiday', null],
        19 => ['holiday_work', 0.5],
        20 => ['holiday_work', 1],
        21 => ['holiday_work', 2],
        22 => ['holiday_work', 3],
        23 => ['holiday_work', 4],
        24 => ['holiday_work', 5],
        25 => ['holiday_work', 6],
        26 => ['holiday_work', 7],
    ];

    public function up(): void
    {
        Schema::table('shift_types', function (Blueprint $table) {
            if (!Schema::hasColumn('shift_types', 'category')) {
                $table->string('category', 40)->nullable()->after('name')->index();
            }
            if (!Schema::hasColumn('shift_types', 'hours')) {
                $table->decimal('hours', 4, 2)->nullable()->after('category');
            }
        });

        $communityId = DB::table('communities')->where('slug', self::SLUG)->value('id');
        if (!$communityId) {
            return;
        }

        foreach (self::SEED as $id => [$category, $hours]) {
            DB::table('shift_types')
                ->where('community_id', $communityId)
                ->where('id', $id)
                ->update(['category' => $category, 'hours' => $hours]);
        }
    }

    public function down(): void
    {
        Schema::table('shift_types', function (Blueprint $table) {
            foreach (['category', 'hours'] as $col) {
                if (Schema::hasColumn('shift_types', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
