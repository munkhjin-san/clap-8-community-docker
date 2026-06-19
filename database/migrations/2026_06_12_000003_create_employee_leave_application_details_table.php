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
        Schema::create('employee_leave_application_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_change_application_id');
            $table->foreign('employee_change_application_id', 'employee_leave_app_fk')
                ->references('id')
                ->on('employee_change_applications')
                ->cascadeOnDelete();
            $table->string('leave_type', 50)->index();
            $table->string('illness_name')->nullable();
            $table->date('start_date')->nullable()->index();
            $table->date('end_date')->nullable()->index();
            $table->date('expected_birth_date')->nullable();
            $table->date('maternity_leave_start')->nullable();
            $table->date('maternity_leave_end')->nullable();
            $table->date('childcare_leave_start')->nullable();
            $table->date('childcare_leave_end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_application_details');
    }
};
