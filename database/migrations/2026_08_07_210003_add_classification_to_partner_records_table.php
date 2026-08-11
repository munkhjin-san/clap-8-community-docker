<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 区分（法人／個人）と取引区分。
 *
 * どちらも当システムのみの項目として持ち、freeeへは同期しない。
 * 取引区分はMISO固有の分類でfreeeに相当する項目が無い。区分はfreeeの `org_code` が
 * 近い可能性があるが、取り得る値の意味を公式スキーマで確認できなかったため、
 * 誤った事業所種別をfreeeへ書き込まないよう同期対象から外している。
 *
 * 値はラベルではなくキーで保存する（表示名を変えても保存済みデータが壊れないため）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_records', function (Blueprint $table) {
            $table->string('entity_type', 20)->nullable()->after('long_name')
                ->comment('区分: corporate=法人 / individual=個人');
            $table->string('transaction_category', 40)->nullable()->after('entity_type')
                ->comment('取引区分: client / partner / property_vehicle_parking / payable / other');

            $table->index('entity_type');
            $table->index('transaction_category');
        });
    }

    public function down(): void
    {
        Schema::table('partner_records', function (Blueprint $table) {
            $table->dropIndex(['entity_type']);
            $table->dropIndex(['transaction_category']);
            $table->dropColumn(['entity_type', 'transaction_category']);
        });
    }
};
