<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_plan_locks', function (Blueprint $table) {
            $table->id();
            $table->integer('project_record_id');
            $table->foreignId('project_plan_year_id')->constrained('project_plan_years')->cascadeOnDelete();
            $table->foreignId('project_plan_scenario_id')->nullable()->constrained('project_plan_scenarios')->nullOnDelete();
            $table->unsignedBigInteger('scenario_key')->default(0); // 0 = base/no-scenario
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->foreign('project_record_id')
                ->references('id')
                ->on('project_records')
                ->cascadeOnDelete();
            $table->unique(['project_record_id', 'project_plan_year_id', 'scenario_key'], 'uniq_project_plan_lock');
            $table->index(['project_record_id', 'project_plan_year_id'], 'idx_project_plan_lock_project_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_plan_locks');
    }
};
