<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_form_block_elements', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_form_block_elements', 'has_file_attachment')) {
                $table->boolean('has_file_attachment')->default(false)->after('has_sub_text_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_form_block_elements', function (Blueprint $table) {
            if (Schema::hasColumn('custom_form_block_elements', 'has_file_attachment')) {
                $table->dropColumn('has_file_attachment');
            }
        });
    }
};
