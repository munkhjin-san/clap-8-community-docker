<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // In-place content renewal: retired materials are kept (user history
        // references material_id) but hidden from new/first-time learners.
        Schema::table('lesson_materials', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_materials', 'retired_at')) {
                $table->timestamp('retired_at')->nullable()->index()->after('deleted_at');
            }
        });

        // Many portfolios per theme per user — one per learning attempt.
        Schema::table('lesson_portfolios', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_portfolios', 'attempt_no')) {
                $table->unsignedInteger('attempt_no')->default(1)->after('lesson_theme_id');
            }
        });

        DB::table('lesson_portfolios')->whereNull('attempt_no')->update(['attempt_no' => 1]);
    }

    public function down(): void
    {
        Schema::table('lesson_materials', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_materials', 'retired_at')) {
                $table->dropColumn('retired_at');
            }
        });

        Schema::table('lesson_portfolios', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_portfolios', 'attempt_no')) {
                $table->dropColumn('attempt_no');
            }
        });
    }
};
