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
        Schema::create('project_cases', function (Blueprint $table) {
            $table->id();
            $table->integer('project_record_id');
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->date('report_date')->comment('対象月（1日で揃える）');
            $table->string('status', 32);
            $table->string('client_name')->nullable();
            $table->unsignedInteger('case_count')->default(0);
            $table->unsignedBigInteger('amount')->default(0);
            $table->text('notes')->nullable();
            $table->string('state', 16)->default('draft')->comment('draft | submitted');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->foreign('project_record_id')
                ->references('id')
                ->on('project_records')
                ->cascadeOnDelete();
            $table->index(['project_record_id', 'report_date']);
            $table->index(['project_record_id', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_cases');
    }
};

