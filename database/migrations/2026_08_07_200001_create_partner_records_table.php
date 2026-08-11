<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 取引先マスタ。freee会計の取引先（/api/1/partners）と1対1で対応させる。
 *
 * 項目はfreeeの取引先に合わせてある。双方向で同期するため、こちらにしか無い項目を
 * 増やすとfreeeへ書き戻せない差分になる。追加するときはfreee側の受け口も確認すること。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_records', function (Blueprint $table) {
            $table->id();

            // 名前が突き合わせの実質キー。freeeの取引先APIには重複を防ぐキーが無く、
            // codeは既存データのほとんどがNULLのため使えない（部門連携と同じ事情）。
            $table->string('name')->comment('取引先名。freeeとの突き合わせに使う');
            $table->string('name_kana')->nullable();
            $table->string('long_name')->nullable()->comment('正式名称');
            $table->string('code')->nullable()->comment('取引先コード');

            $table->string('corporate_number', 13)->nullable()->comment('法人番号13桁');
            $table->string('invoice_registration_number', 14)->nullable()->comment('適格請求書発行事業者登録番号 T+13桁');

            $table->string('postal_code', 8)->nullable();
            $table->unsignedSmallInteger('prefecture_code')->nullable()->comment('freeeの都道府県コード 0〜46');
            $table->string('address_1')->nullable()->comment('市区町村・番地');
            $table->string('address_2')->nullable()->comment('建物名・部屋番号');

            $table->string('phone')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('note')->nullable();

            $table->boolean('available')->default(true)->comment('freeeのavailableと対応。falseは使用不可');

            // 有無がそのまま連携状態。部門連携（project_records.freee_section_id）と同じ約束。
            $table->unsignedBigInteger('freee_partner_id')->nullable()->comment('freee会計の取引先ID');
            $table->timestamp('freee_synced_at')->nullable()->comment('最後にfreeeと突き合わせた時刻');
            $table->date('freee_update_date')->nullable()->comment('取り込み時点のfreee側最終更新日。freeeは日付単位でしか返さない');
            // 双方向同期の要。前回同期時点のfreeeの値を持っておくことで、
            // 「こちらが変えた」のか「freee側が変えた」のかを項目ごとに区別できる。
            // これが無いと後から書いた方が黙って相手の編集を潰す。
            $table->json('freee_snapshot')->nullable()->comment('前回同期時点のfreee側の値');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // 同じfreee取引先を2行に紐付けさせない（MySQLはNULLの重複を許すので未連携行は複数可）
            $table->unique('freee_partner_id');
            // 名前の一意はDBでは張らない。論理削除済みの行と衝突して復元・再登録ができなくなるため、
            // 重複はバリデーション（deleted_at is null に限定）で止める。
            $table->index('name');
            $table->index('available');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_records');
    }
};
