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
        Schema::create('timecard_project_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timecard_record_id')->constrained()->onDelete('cascade');
            $table->integer('project_id');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('minutes')->default(0);
            $table->json('details')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreign('project_id')->references('id')->on('project_records');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timecard_project_segments');
    }
};
