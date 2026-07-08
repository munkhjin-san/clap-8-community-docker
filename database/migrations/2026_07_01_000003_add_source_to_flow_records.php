<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_records', function (Blueprint $table) {
            // Provenance of the record. e.g. source='kintone', source_id=<kintone app id>.
            $table->string('source', 32)->nullable()->after('current_status_id');
            $table->string('source_id', 64)->nullable()->after('source');
            $table->index(['source', 'source_id'], 'flow_records_source_idx');
        });
    }

    public function down(): void
    {
        Schema::table('flow_records', function (Blueprint $table) {
            $table->dropIndex('flow_records_source_idx');
            $table->dropColumn(['source', 'source_id']);
        });
    }
};
