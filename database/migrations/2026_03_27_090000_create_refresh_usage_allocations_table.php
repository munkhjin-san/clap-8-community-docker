<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refresh_usage_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refresh_usage_id')->constrained('refresh_usages')->cascadeOnDelete();
            $table->foreignId('refresh_grant_id')->constrained('refresh_grants')->cascadeOnDelete();
            $table->integer('amount');
            $table->timestamps();

            $table->index(['refresh_usage_id', 'refresh_grant_id'], 'refresh_usage_allocations_usage_grant_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_usage_allocations');
    }
};
