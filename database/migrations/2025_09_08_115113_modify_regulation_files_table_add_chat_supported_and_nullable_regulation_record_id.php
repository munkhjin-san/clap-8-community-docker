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
            // Add chat_supported boolean column
            $table->boolean('chat_supported')->default(false)->after('size');
            
            // Make regulation_record_id nullable
            $table->unsignedBigInteger('regulation_record_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regulation_files', function (Blueprint $table) {
            // Remove chat_supported column
            $table->dropColumn('chat_supported');
            
            // Make regulation_record_id not nullable again
            $table->unsignedBigInteger('regulation_record_id')->nullable(false)->change();
        });
    }
};
