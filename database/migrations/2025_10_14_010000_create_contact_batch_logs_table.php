<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_batch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_batch_id')->constrained()->cascadeOnDelete();
            $table->string('stage')->index();
            $table->string('model')->nullable();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_batch_logs');
    }
};
