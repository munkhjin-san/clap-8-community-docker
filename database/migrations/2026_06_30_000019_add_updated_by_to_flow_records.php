<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_records', function (Blueprint $table) {
            if (!Schema::hasColumn('flow_records', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->index()->after('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flow_records', function (Blueprint $table) {
            if (Schema::hasColumn('flow_records', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });
    }
};
