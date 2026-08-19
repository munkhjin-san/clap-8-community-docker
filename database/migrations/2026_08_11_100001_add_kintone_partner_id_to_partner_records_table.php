<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kintone側の取引先連番（アプリ118の「取引先id」）。
 *
 * 契約書（アプリ138）は取引先をこの番号で参照している。名前で突き合わせると
 * 1792件中1354件（76%）しか一致しないが、この番号なら1788件（99.8%）一致する。
 * freeeの取引先ID（アプリ118の大文字「取引先ID」）とは別物なので混同しないこと。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_records', function (Blueprint $table) {
            $table->unsignedBigInteger('kintone_partner_id')->nullable()->after('freee_partner_id')
                ->comment('kintoneアプリ118の「取引先id」。契約書との突き合わせキー');
            // 同じ番号が2行に付かないようにする（MySQLはNULLの重複を許す）。
            $table->unique('kintone_partner_id');
        });
    }

    public function down(): void
    {
        Schema::table('partner_records', function (Blueprint $table) {
            $table->dropUnique(['kintone_partner_id']);
            $table->dropColumn('kintone_partner_id');
        });
    }
};
