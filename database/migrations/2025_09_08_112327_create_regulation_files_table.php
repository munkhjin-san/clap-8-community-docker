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
        Schema::create('regulation_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regulation_record_id');
            $table->string('vector_file_id');
            $table->string('mime_type')->nullable();
            $table->string('extension')->nullable();
            $table->string('name');
            $table->string('path');
            $table->bigInteger('size')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('regulation_record_id')->references('id')->on('regulation_records');
            $table->index(['regulation_record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regulation_files');
    }
};
