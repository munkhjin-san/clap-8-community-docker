<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The tag system was replaced by multi-select contact types.
        Schema::dropIfExists('contact_record_tag');
        Schema::dropIfExists('contact_tags');
    }

    public function down(): void
    {
        Schema::create('contact_tags', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('category', 30)->nullable()->index();
            $table->timestamps();
            $table->unique('title');
        });

        Schema::create('contact_record_tag', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contact_record_id');
            $table->foreign('contact_record_id')->references('id')->on('contact_records')->cascadeOnDelete();
            $table->foreignId('contact_tag_id')->constrained('contact_tags')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['contact_record_id', 'contact_tag_id']);
        });
    }
};
