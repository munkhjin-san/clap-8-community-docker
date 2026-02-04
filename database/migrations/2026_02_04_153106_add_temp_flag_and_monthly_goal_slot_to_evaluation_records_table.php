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
        Schema::table('evaluation_records', function (Blueprint $table) {
            $table->boolean('temp_flag')->default(false)->before('created_at');
            $table->integer('monthly_goal_slot')->default(6)->before('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_records', function (Blueprint $table) {
            $table->dropColumn(['temp_flag', 'monthly_goal_slot']);
        });
    }
};
