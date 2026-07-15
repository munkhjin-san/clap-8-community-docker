<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_theme_ai_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('lesson_theme_id')->unique();
            $table->string('model')->nullable();
            $table->longText('instructions')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->foreign('lesson_theme_id')->references('id')->on('lesson_themes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_theme_ai_configs');
    }
};
