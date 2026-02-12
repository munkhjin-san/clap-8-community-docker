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
        Schema::create('status_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->integer('record_id');
            $table->integer('before_number')->nullable();
            $table->integer('after_number')->nullable();
            $table->string('before_text')->nullable();
            $table->string('after_text')->nullable();

            $table->index(['type', 'record_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_logs');
    }
};
