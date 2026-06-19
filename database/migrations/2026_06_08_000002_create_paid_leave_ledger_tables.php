<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paid_leave_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_code', 64)->nullable();
            $table->date('joined_date')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('last_granted_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('source_system', 32)->default('glowd');
            $table->unsignedBigInteger('source_app_id')->nullable();
            $table->string('source_record_id', 64)->nullable();
            $table->json('source_payload')->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('user_code');
            $table->index(['active', 'joined_date']);
        });

        Schema::create('paid_leave_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paid_leave_account_id')->constrained('paid_leave_accounts')->cascadeOnDelete();
            $table->foreignId('paid_leave_policy_id')->nullable()->constrained('paid_leave_policies')->nullOnDelete();
            $table->string('grant_type', 32)->default('annual');
            $table->date('granted_at');
            $table->date('expires_at')->nullable();
            $table->unsignedSmallInteger('service_months')->nullable();
            $table->decimal('grant_days', 6, 2);
            $table->integer('amount_minutes');
            $table->integer('remaining_minutes');
            $table->integer('planned_required_minutes')->default(0);
            $table->json('policy_snapshot')->nullable();
            $table->string('source_system', 32)->default('glowd');
            $table->string('source_key', 160)->nullable();
            $table->unsignedBigInteger('source_app_id')->nullable();
            $table->string('source_record_id', 64)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['paid_leave_account_id', 'source_system', 'source_key'], 'paid_leave_grants_account_source_unique');
            $table->index(['paid_leave_account_id', 'granted_at']);
            $table->index(['paid_leave_account_id', 'expires_at']);
        });

        Schema::create('paid_leave_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paid_leave_account_id')->constrained('paid_leave_accounts')->cascadeOnDelete();
            $table->foreignId('shift_record_id')->nullable()->constrained('shift_records')->nullOnDelete();
            $table->date('used_on');
            $table->integer('amount_minutes');
            $table->string('usage_type', 32)->default('shift');
            $table->string('status', 32)->default('confirmed');
            $table->string('source_system', 32)->default('glowd');
            $table->string('source_key', 160)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['source_system', 'source_key'], 'paid_leave_usages_source_unique');
            $table->index(['paid_leave_account_id', 'used_on']);
        });

        Schema::create('paid_leave_usage_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paid_leave_usage_id')->constrained('paid_leave_usages')->cascadeOnDelete();
            $table->foreignId('paid_leave_grant_id')->constrained('paid_leave_grants')->cascadeOnDelete();
            $table->integer('amount_minutes');
            $table->timestamps();

            $table->unique(['paid_leave_usage_id', 'paid_leave_grant_id'], 'paid_leave_usage_grant_unique');
        });

        Schema::create('paid_leave_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paid_leave_account_id')->constrained('paid_leave_accounts')->cascadeOnDelete();
            $table->foreignId('paid_leave_grant_id')->nullable()->constrained('paid_leave_grants')->nullOnDelete();
            $table->date('adjusted_on');
            $table->integer('amount_minutes');
            $table->string('adjustment_type', 32)->default('manual');
            $table->string('source_system', 32)->default('glowd');
            $table->string('source_key', 160)->nullable();
            $table->unsignedBigInteger('source_app_id')->nullable();
            $table->string('source_record_id', 64)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['source_system', 'source_key'], 'paid_leave_adjustments_source_unique');
            $table->index(['paid_leave_account_id', 'adjusted_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paid_leave_adjustments');
        Schema::dropIfExists('paid_leave_usage_allocations');
        Schema::dropIfExists('paid_leave_usages');
        Schema::dropIfExists('paid_leave_grants');
        Schema::dropIfExists('paid_leave_accounts');
    }
};
