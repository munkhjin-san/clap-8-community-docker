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
        Schema::table('project_specs', function (Blueprint $table) {
            $table->json('plan_data')->nullable()->after('spec_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_specs', function (Blueprint $table) {
            $table->dropColumn('plan_data');
        });
    }
};
