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
        Schema::create('contact_record_comments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contact_record_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
            $table->foreign('contact_record_id')->references('id')->on('contact_records')->cascadeOnDelete();
            $table->index('contact_record_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_record_comments');
    }
};
