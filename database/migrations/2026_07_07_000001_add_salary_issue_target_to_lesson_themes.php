<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_themes', function (Blueprint $table) {
            $table->boolean('salary_issue_target')->default(false)->after('archive');
        });

        // Preserve current behavior: the 9 predefined salary-issue themes were
        // previously identified by the hardcoded `id <= 10` filter.
        DB::table('lesson_themes')->where('id', '<=', 10)->update(['salary_issue_target' => true]);
    }

    public function down(): void
    {
        Schema::table('lesson_themes', function (Blueprint $table) {
            $table->dropColumn('salary_issue_target');
        });
    }
};
