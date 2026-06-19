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
        Schema::create('employee_profile_change_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_change_application_id');
            $table->foreign('employee_change_application_id', 'employee_profile_change_app_fk')
                ->references('id')
                ->on('employee_change_applications')
                ->cascadeOnDelete();
            $table->string('change_type', 50)->index();
            $table->date('effective_date')->nullable()->index();
            $table->text('reason')->nullable();

            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name_kana')->nullable();
            $table->string('first_name_kana')->nullable();
            $table->text('address')->nullable();

            $table->string('dependent_action', 20)->nullable()->index();
            $table->string('relationship')->nullable();
            $table->string('annual_income')->nullable();
            $table->string('dependent_name')->nullable();
            $table->string('dependent_name_kana')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->text('dependent_address')->nullable();
            $table->date('retired_on')->nullable();
            $table->date('employment_on')->nullable();

            $table->string('work_location')->nullable();
            $table->text('route')->nullable();
            $table->string('monthly_pass_amount')->nullable();
            $table->string('one_way_distance')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_profile_change_details');
    }
};
