<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 情報管理ブロック（認証番号 + ヒアリングシート）。
 *
 * freeeの取引先にはいずれも対応する項目が無いため、すべて当システムのみの情報で
 * 同期対象には含めない（PartnerRecord の FREEE_PULL/PUSH_FIELDS に入れないこと）。
 *
 * 回答は「設問キー => 真偽値」のJSONで持つ。配列の添字ではなくキー（is_01 / lc_01…）に
 * するのは、設問の並び替えや途中追加で既存の回答がずれないようにするため。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_records', function (Blueprint $table) {
            $table->string('isms_registration_number')->nullable()->after('note')
                ->comment('ISMS認証登録番号');
            $table->string('privacy_mark_number')->nullable()->after('isms_registration_number')
                ->comment('プライバシーマーク許諾番号');
            $table->json('information_security_answers')->nullable()->after('privacy_mark_number')
                ->comment('情報セキュリティのヒアリング回答 {is_01: true, ...}');
            $table->json('labor_contract_answers')->nullable()->after('information_security_answers')
                ->comment('労働契約に関する質問の回答 {lc_01: true, ...}');
        });
    }

    public function down(): void
    {
        Schema::table('partner_records', function (Blueprint $table) {
            $table->dropColumn([
                'isms_registration_number',
                'privacy_mark_number',
                'information_security_answers',
                'labor_contract_answers',
            ]);
        });
    }
};
