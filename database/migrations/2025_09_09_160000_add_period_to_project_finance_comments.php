<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_finance_comments', function (Blueprint $table) {
            $table->string('period', 7)->nullable()->after('type')->index();
        });

        DB::statement("UPDATE project_finance_comments SET period = DATE_FORMAT(created_at, '%Y-%m') WHERE period IS NULL");
    }

    public function down(): void
    {
        Schema::table('project_finance_comments', function (Blueprint $table) {
            $table->dropIndex(['period']);
            $table->dropColumn('period');
        });
    }
};
