<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actual_result_reports', function (Blueprint $table) {
            $table->id();
            $table->date('target_month')->unique();
            $table->unsignedBigInteger('current_upload_id')->nullable();
            $table->json('file_metadata')->nullable();
            $table->json('summary')->nullable();
            $table->json('account_totals')->nullable();
            $table->json('result_payload')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index('target_month');
        });

        Schema::create('actual_result_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actual_result_report_id')->nullable()->constrained('actual_result_reports')->nullOnDelete();
            $table->date('target_month');
            $table->string('original_name')->nullable();
            $table->string('stored_path');
            $table->string('file_hash', 64)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->json('file_metadata')->nullable();
            $table->json('calculated_summary')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['target_month', 'created_at']);
            $table->index('file_hash');
        });

        Schema::table('actual_result_reports', function (Blueprint $table) {
            $table->foreign('current_upload_id')->references('id')->on('actual_result_uploads')->nullOnDelete();
        });

        Schema::create('actual_result_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actual_result_report_id')->constrained('actual_result_reports')->cascadeOnDelete();
            $table->integer('project_record_id')->nullable();
            $table->string('department_name');
            $table->json('source_departments')->nullable();
            $table->json('metrics')->nullable();
            $table->json('accounts')->nullable();
            $table->integer('external_sales')->default(0);
            $table->integer('internal_sales')->default(0);
            $table->integer('sales')->default(0);
            $table->integer('cost_of_goods_sold')->default(0);
            $table->integer('sg_and_a_expenses')->default(0);
            $table->integer('indirect_allocation_expense')->default(0);
            $table->integer('normal_profit')->default(0);
            $table->integer('performance_bonus_reserve')->default(0);
            $table->integer('real_profit')->default(0);
            $table->decimal('real_margin', 8, 2)->nullable();
            $table->integer('basic_bonus_reserve')->default(0);
            $table->integer('paid_leave_reserve')->default(0);
            $table->integer('welfare_reserve')->default(0);
            $table->integer('refresh_reserve')->default(0);
            $table->boolean('manual_adjusted')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('project_record_id')->references('id')->on('project_records')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['actual_result_report_id', 'department_name'], 'actual_result_dept_report_name_unique');
            $table->index(['project_record_id', 'actual_result_report_id'], 'actual_result_dept_project_report_idx');
        });

        Schema::create('actual_result_edit_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actual_result_report_id')->constrained('actual_result_reports')->cascadeOnDelete();
            $table->foreignId('actual_result_department_id')->nullable()->constrained('actual_result_departments')->nullOnDelete();
            $table->integer('project_record_id')->nullable();
            $table->string('department_name');
            $table->string('action', 40);
            $table->string('account_key')->nullable();
            $table->json('before_value')->nullable();
            $table->json('after_value')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->timestamps();

            $table->foreign('project_record_id')->references('id')->on('project_records')->nullOnDelete();
            $table->foreign('edited_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['actual_result_report_id', 'created_at'], 'actual_result_history_report_created_idx');
            $table->index(['project_record_id', 'created_at'], 'actual_result_history_project_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actual_result_edit_histories');
        Schema::dropIfExists('actual_result_departments');

        Schema::table('actual_result_reports', function (Blueprint $table) {
            $table->dropForeign(['current_upload_id']);
        });

        Schema::dropIfExists('actual_result_uploads');
        Schema::dropIfExists('actual_result_reports');
    }
};
