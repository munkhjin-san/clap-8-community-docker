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
        Schema::table('timecard_records', function (Blueprint $table) {
            $table->unsignedInteger('car_used_project')->nullable()->after('car_mileage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timecard_records', function (Blueprint $table) {
            $table->dropIndex(['car_used_project']); // drop if you added it
            $table->dropColumn('car_used_project');
        });
    }
};
