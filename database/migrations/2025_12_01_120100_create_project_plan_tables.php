<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_plan_years', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., FY2026
            $table->string('name');
            $table->unsignedSmallInteger('fiscal_year'); // label year (e.g., 2026 for Mar 2025–Feb 2026)
            $table->unsignedTinyInteger('start_month')->default(3); // 1–12
            $table->date('starts_on'); // fiscal year start date
            $table->unsignedTinyInteger('months')->default(12);
            $table->timestamps();
        });

        Schema::create('project_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('project_record_id');
            $table->foreignId('parent_id')->nullable()->constrained('project_accounts')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('path'); // materialized path: /6000/6020/
            $table->unsignedTinyInteger('depth')->default(0);
            $table->boolean('is_postable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('project_record_id')
                ->references('id')
                ->on('project_records')
                ->cascadeOnDelete();
            $table->unique(['project_record_id', 'code']);
            $table->unique(['project_record_id', 'path']);
            $table->index(['project_record_id', 'parent_id']);
        });

        Schema::create('project_plan_scenarios', function (Blueprint $table) {
            $table->id();
            $table->integer('project_record_id');
            $table->string('code'); // e.g., base, stretch, worst
            $table->string('name');
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('project_record_id')
                ->references('id')
                ->on('project_records')
                ->cascadeOnDelete();
            $table->unique(['project_record_id', 'code']);
        });

        Schema::create('project_plan_amounts', function (Blueprint $table) {
            $table->id();
            $table->integer('project_record_id');
            $table->foreignId('project_plan_year_id')->constrained('project_plan_years')->cascadeOnDelete();
            $table->foreignId('project_account_id')->constrained('project_accounts')->cascadeOnDelete();
            $table->foreignId('project_plan_scenario_id')->nullable()->constrained('project_plan_scenarios')->nullOnDelete();
            $table->unsignedTinyInteger('period_index'); // 1–12
            $table->decimal('amount', 18, 2)->default(0);
            // stored generated caused MySQL error in some setups; keep a plain column populated by app logic
            $table->unsignedBigInteger('scenario_key')->default(0);
            $table->timestamps();
            $table->foreign('project_record_id')
                ->references('id')
                ->on('project_records')
                ->cascadeOnDelete();
            $table->unique(
                ['project_record_id', 'project_plan_year_id', 'project_account_id', 'period_index', 'scenario_key'],
                'uniq_project_plan_amount'
            );
            $table->index(['project_plan_year_id', 'period_index'], 'idx_plan_year_period');
        });

        DB::statement('DROP VIEW IF EXISTS project_v_plan_months');
        DB::statement("
            CREATE VIEW project_v_plan_months AS
            WITH RECURSIVE seq AS (
                SELECT 1 AS n
                UNION ALL
                SELECT n + 1 FROM seq WHERE n < 12
            )
            SELECT
                (py.id * 100 + seq.n) AS plan_month_id,
                py.id AS plan_year_id,
                seq.n AS period_index,
                YEAR(DATE_ADD(py.starts_on, INTERVAL seq.n - 1 MONTH)) AS calendar_year,
                MONTH(DATE_ADD(py.starts_on, INTERVAL seq.n - 1 MONTH)) AS calendar_month
            FROM project_plan_years py
            CROSS JOIN seq
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS project_v_plan_months');
        Schema::dropIfExists('project_plan_amounts');
        Schema::dropIfExists('project_plan_scenarios');
        Schema::dropIfExists('project_accounts');
        Schema::dropIfExists('project_plan_years');
    }
};
