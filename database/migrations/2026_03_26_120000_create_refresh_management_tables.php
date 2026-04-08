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
        Schema::create('refresh_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('joined_date')->nullable();
            $table->integer('opening_total_granted')->default(0);
            $table->integer('opening_total_used')->default(0);
            $table->integer('opening_remaining_amount')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('is_active');
        });

        Schema::create('refresh_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refresh_account_id')->constrained('refresh_accounts')->cascadeOnDelete();
            $table->string('grant_type', 30);
            $table->unsignedSmallInteger('grant_year')->nullable();
            $table->date('granted_at');
            $table->date('expires_at')->nullable();
            $table->integer('amount');
            $table->integer('remaining_amount')->nullable();
            $table->text('note')->nullable();
            $table->string('source_system', 20)->default('glowd');
            $table->string('source_key', 100)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['refresh_account_id', 'granted_at']);
            $table->index(['refresh_account_id', 'grant_year']);
            $table->index(['refresh_account_id', 'expires_at']);
            $table->index(['refresh_account_id', 'source_system', 'source_key'], 'refresh_grants_account_source_key_index');
        });

        Schema::create('refresh_expirations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refresh_account_id')->constrained('refresh_accounts')->cascadeOnDelete();
            $table->foreignId('refresh_grant_id')->nullable()->constrained('refresh_grants')->nullOnDelete();
            $table->date('expired_at');
            $table->integer('amount');
            $table->text('note')->nullable();
            $table->string('source_system', 20)->default('glowd');
            $table->string('source_key', 100)->nullable();
            $table->timestamps();

            $table->index(['refresh_account_id', 'expired_at']);
            $table->unique(['refresh_grant_id', 'expired_at'], 'refresh_expirations_grant_expired_at_unique');
            $table->index(['refresh_account_id', 'source_system', 'source_key'], 'refresh_expirations_account_source_key_index');
        });

        Schema::create('refresh_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refresh_account_id')->constrained('refresh_accounts')->cascadeOnDelete();
            $table->integer('post_record_id')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->integer('amount');
            $table->string('status', 20)->default('pending');
            $table->text('note')->nullable();
            $table->string('source_system', 20)->default('glowd');
            $table->string('source_key', 100)->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique('post_record_id');
            $table->index(['refresh_account_id', 'used_at']);
            $table->index(['refresh_account_id', 'source_system', 'source_key'], 'refresh_usages_account_source_key_index');
        });

        Schema::create('refresh_annual_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refresh_account_id')->constrained('refresh_accounts')->cascadeOnDelete();
            $table->unsignedSmallInteger('grant_year');
            $table->integer('base_amount')->default(0);
            $table->integer('adjusted_amount')->default(0);
            $table->string('attendance_status', 100)->nullable();
            $table->string('leave_status', 100)->nullable();
            $table->unsignedSmallInteger('service_years')->nullable();
            $table->text('decision_note')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['refresh_account_id', 'grant_year'], 'refresh_annual_reviews_account_year_unique');
            $table->index(['grant_year', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_annual_reviews');
        Schema::dropIfExists('refresh_usages');
        Schema::dropIfExists('refresh_expirations');
        Schema::dropIfExists('refresh_grants');
        Schema::dropIfExists('refresh_accounts');
    }
};
