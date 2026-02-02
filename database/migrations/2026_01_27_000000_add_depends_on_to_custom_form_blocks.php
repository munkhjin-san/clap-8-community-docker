<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_form_blocks', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_form_blocks', 'depends_on')) {
                $table->json('depends_on')->nullable()->after('placeholder');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_form_blocks', function (Blueprint $table) {
            if (Schema::hasColumn('custom_form_blocks', 'depends_on')) {
                $table->dropColumn('depends_on');
            }
        });
    }
};
