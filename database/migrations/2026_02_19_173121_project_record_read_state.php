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
        Schema::create('project_record_read_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('project_record_id');
            $table->foreign('project_record_id')
                ->references('id')
                ->on('project_records')
                ->cascadeOnDelete();
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['project_record_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_record_read_states');
    }
};
