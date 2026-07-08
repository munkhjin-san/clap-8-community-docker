<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_fields', function (Blueprint $table) {
            if (!Schema::hasColumn('flow_fields', 'formula')) {
                $table->text('formula')->nullable()->after('validation');
            }
            if (!Schema::hasColumn('flow_fields', 'result_type')) {
                $table->string('result_type')->nullable()->after('formula');
            }
            if (!Schema::hasColumn('flow_fields', 'hidden')) {
                $table->boolean('hidden')->default(false)->after('is_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flow_fields', function (Blueprint $table) {
            foreach (['formula', 'result_type', 'hidden'] as $col) {
                if (Schema::hasColumn('flow_fields', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
