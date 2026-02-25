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
        Schema::table('project_records', function (Blueprint $table) {
            $table->date('contract_started_at')->nullable()->after('transitioned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_records', function (Blueprint $table) {
            $table->dropColumn('contract_started_at');
        });
    }
};
