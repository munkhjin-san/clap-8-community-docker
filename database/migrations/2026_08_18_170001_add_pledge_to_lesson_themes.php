<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_themes', function (Blueprint $table) {
            // 誓約書: when on, the learner must sign the uploaded PDF before the
            // theme can complete. pledge_file_path holds the blank original
            // (a /lesson_files/... path, same as material content references).
            $table->boolean('pledge')->default(false)->after('custom_form_id');
            $table->string('pledge_file_path')->nullable()->after('pledge');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_themes', function (Blueprint $table) {
            $table->dropColumn(['pledge', 'pledge_file_path']);
        });
    }
};
