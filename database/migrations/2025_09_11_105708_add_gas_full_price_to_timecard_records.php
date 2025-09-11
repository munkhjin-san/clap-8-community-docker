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
            $table->integer('gas_full_price')->default(0)->after('car_used_project');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timecard_records', function (Blueprint $table) {
            $table->dropColumn('gas_full_price');
        });
    }
};
