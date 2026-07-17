<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_fields', function (Blueprint $table) {
            if (!Schema::hasColumn('flow_fields', 'validation')) {
                $table->json('validation')->nullable()->after('depends_on');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flow_fields', function (Blueprint $table) {
            if (Schema::hasColumn('flow_fields', 'validation')) {
                $table->dropColumn('validation');
            }
        });
    }
};
