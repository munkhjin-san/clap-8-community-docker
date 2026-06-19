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
        Schema::create('planned_leave_change_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shift_record_id')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->date('original_date');
            $table->date('requested_date');
            $table->string('reason')->nullable();
            $table->boolean('pm_approval_required')->default(false);
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('pm_id')->nullable();
            $table->timestamp('pm_approval_date')->nullable();
            $table->timestamp('approval_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('shift_record_id', 'plcr_sr_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planned_leave_change_requests');
    }
};
