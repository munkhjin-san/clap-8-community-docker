<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timecard_missing_occurrences', function (Blueprint $table) {
            if (!Schema::hasColumn('timecard_missing_occurrences', 'pm_alerted_at')) {
                $table->timestamp('pm_alerted_at')->nullable()->after('resolved_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('timecard_missing_occurrences', function (Blueprint $table) {
            if (Schema::hasColumn('timecard_missing_occurrences', 'pm_alerted_at')) {
                $table->dropColumn('pm_alerted_at');
            }
        });
    }
};
