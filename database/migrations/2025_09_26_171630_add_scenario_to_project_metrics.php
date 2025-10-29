<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_metrics', function (Blueprint $table) {
            if (! Schema::hasColumn('project_metrics', 'scenario_label_ja')) {
                $table->string('scenario_label_ja')->after('sort_order')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_metrics', function (Blueprint $table) {
            if (Schema::hasColumn('project_metrics', 'scenario_label_ja')) {
                $table->dropColumn('scenario_label_ja');
            }
        });
    }
};
