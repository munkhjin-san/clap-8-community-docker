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
        Schema::create('user_read_histories', function (Blueprint $table) {
            $table->id();
            $table->string('readable_type')->index();
            $table->unsignedBigInteger('readable_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamp('last_read_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['readable_type', 'readable_id', 'user_id'], 'user_read_histories_unique');
            $table->index(['readable_type', 'readable_id', 'last_read_at'], 'user_read_histories_readable_last_read_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_read_histories');
    }
};
