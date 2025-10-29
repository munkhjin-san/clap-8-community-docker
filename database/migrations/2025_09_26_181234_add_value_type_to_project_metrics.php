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
        Schema::table('project_metrics', function (Blueprint $table) {
            if (! Schema::hasColumn('project_metrics', 'value_type')) {
                $table->enum('value_type', ['amount', 'rate', 'currency'])->after('kind')->default('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_metrics', function (Blueprint $table) {
            if (Schema::hasColumn('project_metrics', 'value_type')) {
                $table->dropColumn('value_type');
            }
        });
    }
};
