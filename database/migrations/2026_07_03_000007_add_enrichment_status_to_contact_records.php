<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_records', function (Blueprint $table) {
            // null = never enriched (manual entry). pending/completed/failed for
            // the background company-info enrichment so the UI can show progress.
            $table->string('enrichment_status', 20)->nullable()->after('data');
        });
    }

    public function down(): void
    {
        Schema::table('contact_records', function (Blueprint $table) {
            $table->dropColumn('enrichment_status');
        });
    }
};
