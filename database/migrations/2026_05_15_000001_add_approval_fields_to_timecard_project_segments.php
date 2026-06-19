<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timecard_project_segments', function (Blueprint $table) {
            if (!Schema::hasColumn('timecard_project_segments', 'status')) {
                $table->string('status')->default('draft')->after('minutes');
            }
            if (!Schema::hasColumn('timecard_project_segments', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('timecard_project_segments', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('timecard_project_segments', function (Blueprint $table) {
            if (Schema::hasColumn('timecard_project_segments', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('timecard_project_segments', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('timecard_project_segments', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
