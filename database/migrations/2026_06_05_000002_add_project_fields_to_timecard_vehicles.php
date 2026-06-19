<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('timecard_vehicles')) {
            return;
        }

        Schema::table('timecard_vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('timecard_vehicles', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('user_id')->index();
            }
            if (!Schema::hasColumn('timecard_vehicles', 'timecard_project_segment_id')) {
                $table->unsignedBigInteger('timecard_project_segment_id')->nullable()->after('project_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('timecard_vehicles')) {
            return;
        }

        Schema::table('timecard_vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('timecard_vehicles', 'timecard_project_segment_id')) {
                $table->dropColumn('timecard_project_segment_id');
            }
            if (Schema::hasColumn('timecard_vehicles', 'project_id')) {
                $table->dropColumn('project_id');
            }
        });
    }
};
