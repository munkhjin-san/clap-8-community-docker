<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * プロジェクト（= freeeの部門/Section）の連携状態。
 *
 * freee_section_id の有無がそのまま同期状態を表す（NULL = 未連携）。
 * 同じ部門を複数プロジェクトに割り当てないようUNIQUEを張る
 * （MySQLはNULLの重複を許すので未連携は何件でも可）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_records', function (Blueprint $table) {
            $table->unsignedBigInteger('freee_section_id')->nullable()->after('unit_id')
                ->comment('freee会計の部門ID。NULLなら未連携');
            $table->timestamp('freee_synced_at')->nullable()->after('freee_section_id')
                ->comment('最後にfreeeと突き合わせた時刻');

            $table->unique('freee_section_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_records', function (Blueprint $table) {
            $table->dropUnique(['freee_section_id']);
            $table->dropColumn(['freee_section_id', 'freee_synced_at']);
        });
    }
};
