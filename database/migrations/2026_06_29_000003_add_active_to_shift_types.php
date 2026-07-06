<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Active/inactive flag for shift types. Inactive types are hidden from the shift
 * selection dropdown (getAvailableShiftTypes) but are STILL resolved by category
 * (idsFor/idFor) and counted by existing shift records, so payroll/work-hour
 * calculations are unaffected. Distinct from deleted_flag (full soft-delete).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('shift_types', 'active')) {
            Schema::table('shift_types', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('category')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shift_types', 'active')) {
            Schema::table('shift_types', function (Blueprint $table) {
                $table->dropColumn('active');
            });
        }
    }
};
