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
        Schema::table('project_goals', function (Blueprint $table) {
            $table->string('stakeholder_name')->nullable()->default(null);
            $table->smallInteger('stakeholder_point')->default(0);
            $table->text('stakeholder_review')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_goals', function (Blueprint $table) {
            $table->dropColumn([
                'stakeholder_name',
                'stakeholder_point',
                'stakeholder_review',
            ]);
        });
    }
};
