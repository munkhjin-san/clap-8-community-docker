<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('shift_overtime_requests', 'project_segments')) {
            return;
        }

        Schema::table('shift_overtime_requests', function (Blueprint $table) {
            $table->json('project_segments')->nullable()->after('minutes');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('shift_overtime_requests', 'project_segments')) {
            return;
        }

        Schema::table('shift_overtime_requests', function (Blueprint $table) {
            $table->dropColumn('project_segments');
        });
    }
};
