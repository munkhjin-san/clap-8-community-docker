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
        Schema::table('regulation_files', function (Blueprint $table) {
            // Make vector_file_id nullable
            $table->string('vector_file_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regulation_files', function (Blueprint $table) {
            // Make vector_file_id not nullable again
            $table->string('vector_file_id')->nullable(false)->change();
        });
    }
};
