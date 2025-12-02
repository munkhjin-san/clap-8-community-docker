<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_finance_last_reads', function (Blueprint $table) {
            $table->string('period', 7)->nullable()->after('user_id')->index();
        });
        // DB::statement("UPDATE project_finance_last_reads SET period = DATE_FORMAT(created_at, '%Y-%m') WHERE period IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_finance_last_reads', function (Blueprint $table) {
            $table->dropIndex(['period']);
            $table->dropColumn('period');
        });
    }
};
