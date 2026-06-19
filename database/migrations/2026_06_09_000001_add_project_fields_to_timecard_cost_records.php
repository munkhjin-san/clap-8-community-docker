<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('timecard_cost_records')) {
            return;
        }

        Schema::table('timecard_cost_records', function (Blueprint $table) {
            if (!Schema::hasColumn('timecard_cost_records', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('department')->index();
            }
            if (!Schema::hasColumn('timecard_cost_records', 'timecard_project_segment_id')) {
                $table->unsignedBigInteger('timecard_project_segment_id')->nullable()->after('project_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('timecard_cost_records')) {
            return;
        }

        Schema::table('timecard_cost_records', function (Blueprint $table) {
            if (Schema::hasColumn('timecard_cost_records', 'timecard_project_segment_id')) {
                $table->dropColumn('timecard_project_segment_id');
            }
            if (Schema::hasColumn('timecard_cost_records', 'project_id')) {
                $table->dropColumn('project_id');
            }
        });
    }
};
