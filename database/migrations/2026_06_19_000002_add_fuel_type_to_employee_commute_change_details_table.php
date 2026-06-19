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
        Schema::table('employee_commute_change_details', function (Blueprint $table) {
            $table->string('fuel_type')->nullable()->after('one_way_distance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_commute_change_details', function (Blueprint $table) {
            $table->dropColumn('fuel_type');
        });
    }
};
