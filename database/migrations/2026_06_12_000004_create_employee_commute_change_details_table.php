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
        Schema::create('employee_commute_change_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_change_application_id');
            $table->foreign('employee_change_application_id', 'employee_commute_app_fk')
                ->references('id')
                ->on('employee_change_applications')
                ->cascadeOnDelete();
            $table->string('commute_type', 50)->index();
            $table->date('effective_date')->nullable()->index();
            $table->text('route')->nullable();
            $table->string('pass_amount')->nullable();
            $table->string('other_amount')->nullable();
            $table->string('parking_amount')->nullable();
            $table->string('one_way_distance')->nullable();
            $table->string('car_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_commute_change_details');
    }
};
