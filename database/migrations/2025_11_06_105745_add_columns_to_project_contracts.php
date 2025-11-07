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
        Schema::table('project_contracts', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
            $table->string('role', 32)->nullable();
            $table->string('contract_type', 32)->nullable();
            $table->boolean('active')->default(true);

            $table->index(['active', 'version']);
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_contracts', function (Blueprint $table) {
            $table->dropColumn(['version', 'role', 'contract_type', 'active']);
        });
    }
};
