<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 担当者の役職。
 *
 * freeeの取引先に役職の項目は無い（`default_title` は「様」「御中」の敬称であって役職ではない）。
 * よって法人番号・Webサイト・備考と同じく当システムのみの項目で、同期対象には含めない。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_records', function (Blueprint $table) {
            $table->string('contact_position')->nullable()->after('contact_name')
                ->comment('担当者の役職。freeeには存在しない項目のため同期しない');
        });
    }

    public function down(): void
    {
        Schema::table('partner_records', function (Blueprint $table) {
            $table->dropColumn('contact_position');
        });
    }
};
