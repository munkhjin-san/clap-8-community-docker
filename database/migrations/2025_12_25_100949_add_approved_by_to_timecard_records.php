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
            $table->unsignedBigInteger('approved_by')->nullable()->after('user_id')->comment('Timecard approved by user ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timecard_records', function (Blueprint $table) {
            //
        });
    }
};
