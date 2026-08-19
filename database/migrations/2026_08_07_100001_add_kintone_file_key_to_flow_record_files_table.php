<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * kintoneの fileKey を控える列。
 *
 * 数GB規模の添付を取り込むと、途中で必ず一度は止まる（通信・再認可・こちらの都合）。
 * どれが取り込み済みかを持っていないと、やり直しは「全部消して最初から」か「二重に入る」の
 * どちらかになる。fileKey はkintone側でその実体を一意に指すので、これを控えておけば
 * 「もう有るものは飛ばす」が成り立ち、何度でも続きから流せる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_record_files', function (Blueprint $table) {
            $table->string('kintone_file_key', 191)->nullable()->after('legacy_message_file_id');
            $table->index('kintone_file_key', 'frf_kintone_key_idx');
        });
    }

    public function down(): void
    {
        Schema::table('flow_record_files', function (Blueprint $table) {
            $table->dropIndex('frf_kintone_key_idx');
            $table->dropColumn('kintone_file_key');
        });
    }
};
