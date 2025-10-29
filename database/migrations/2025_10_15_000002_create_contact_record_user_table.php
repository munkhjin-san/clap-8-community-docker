<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_record_user', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contact_record_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('owner');
            $table->timestamps();
            $table->foreign('contact_record_id')->references('id')->on('contact_records')->cascadeOnDelete();
            $table->unique(['contact_record_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_record_user');
    }
};
