<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_definitions', function (Blueprint $table) {
            if (!Schema::hasColumn('flow_definitions', 'project_record_id')) {
                $table->unsignedBigInteger('project_record_id')->nullable()->index()->after('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flow_definitions', function (Blueprint $table) {
            if (Schema::hasColumn('flow_definitions', 'project_record_id')) {
                $table->dropColumn('project_record_id');
            }
        });
    }
};
