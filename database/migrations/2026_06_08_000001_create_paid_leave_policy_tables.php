<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paid_leave_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default');
            $table->boolean('active')->default(true);
            $table->date('effective_from')->nullable();
            $table->unsignedSmallInteger('first_grant_after_months')->default(6);
            $table->unsignedSmallInteger('annual_grant_interval_months')->default(12);
            $table->unsignedSmallInteger('expires_after_months')->default(24);
            $table->decimal('minimum_attendance_rate', 5, 2)->default(80);
            $table->boolean('carryover_enabled')->default(true);
            $table->boolean('hourly_leave_enabled')->default(true);
            $table->unsignedSmallInteger('hourly_deduction_unit_minutes')->default(60);
            $table->unsignedSmallInteger('minutes_per_leave_day')->default(480);
            $table->decimal('max_hourly_leave_days_per_year', 5, 2)->default(5);
            $table->boolean('allow_negative_balance')->default(false);
            $table->text('memo')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->unique('name');
            $table->index(['active', 'effective_from']);
        });

        Schema::create('paid_leave_grant_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paid_leave_policy_id')->constrained('paid_leave_policies')->cascadeOnDelete();
            $table->unsignedSmallInteger('service_months');
            $table->decimal('legal_min_days', 5, 2);
            $table->decimal('grant_days', 5, 2);
            $table->string('label')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('memo')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['paid_leave_policy_id', 'service_months'], 'paid_leave_rules_policy_month_unique');
            $table->index(['paid_leave_policy_id', 'active', 'sort_order'], 'paid_leave_rules_policy_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paid_leave_grant_rules');
        Schema::dropIfExists('paid_leave_policies');
    }
};
