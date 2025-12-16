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
        Schema::table('project_cases', function (Blueprint $table) {
            $table->unsignedBigInteger('timecard_record_id')->nullable();
            $table->unique(['timecard_record_id', 'status']);

            $table->foreign('timecard_record_id')
                ->references('id')
                ->on('timecard_records')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_cases', function (Blueprint $table) {
            //
        });
    }
};
