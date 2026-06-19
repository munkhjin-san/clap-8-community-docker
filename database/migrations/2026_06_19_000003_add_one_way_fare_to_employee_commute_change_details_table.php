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
            $table->string('one_way_fare')->nullable()->after('pass_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_commute_change_details', function (Blueprint $table) {
            $table->dropColumn('one_way_fare');
        });
    }
};
