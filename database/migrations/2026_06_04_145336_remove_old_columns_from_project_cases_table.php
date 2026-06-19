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
        Schema::table('project_cases', function (Blueprint $table) {
            $table->dropColumn([
                'client_name',
                'kind',
                'stage',
                'delivery_status',
                'probability',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_cases', function (Blueprint $table) {
            $table->string('client_name')->nullable();
            $table->string('kind')->nullable();
            $table->string('stage')->nullable();
            $table->string('delivery_status')->nullable();
            $table->integer('probability')->nullable();
        });
    }
};
