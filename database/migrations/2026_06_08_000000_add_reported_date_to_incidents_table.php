<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->date('reported_date')->nullable()->after('reported_by')->index();
        });

        DB::table('incidents')
            ->whereNull('reported_date')
            ->whereNotNull('created_at')
            ->update([
                'reported_date' => DB::raw('DATE(created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('reported_date');
        });
    }
};
