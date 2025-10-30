<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_batches', function (Blueprint $table) {
            $table->unsignedInteger('scan_attempts')->default(0)->after('enrich_operation');
            $table->unsignedInteger('enrich_attempts')->default(0)->after('scan_attempts');
            $table->timestamp('scan_requested_at')->nullable()->after('enrich_attempts');
            $table->timestamp('scan_completed_at')->nullable()->after('scan_requested_at');
            $table->timestamp('enrich_requested_at')->nullable()->after('scan_completed_at');
            $table->timestamp('enrich_completed_at')->nullable()->after('enrich_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('contact_batches', function (Blueprint $table) {
            $table->dropColumn([
                'scan_attempts',
                'enrich_attempts',
                'scan_requested_at',
                'scan_completed_at',
                'enrich_requested_at',
                'enrich_completed_at',
            ]);
        });
    }
};
