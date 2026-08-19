<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 移行してきたコメントの、元の書き手と出どころ。
 *
 * kintoneのコメントはあちらのアカウントが書いたもので、こちらのユーザーとは一致しない。
 * user_id は空のまま（＝誰にも結び付けない）にするが、**名前は残す**：
 * 「誰が言ったか」が分からないやり取りは、読み返しても意味を取れないことが多い。
 * 画面ではリンクにならないただの文字として出す。
 *
 * kintone_comment_key は再実行の目印。数千件のコメントを入れる途中で止まったとき、
 * これが無いと二重に入るか、全部消してやり直すかになる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_comments', function (Blueprint $table) {
            $table->string('legacy_author', 191)->nullable()->after('user_id');
            $table->string('kintone_comment_key', 191)->nullable()->after('legacy_author');
            $table->index('kintone_comment_key', 'app_comments_kintone_key_idx');
        });
    }

    public function down(): void
    {
        Schema::table('app_comments', function (Blueprint $table) {
            $table->dropIndex('app_comments_kintone_key_idx');
            $table->dropColumn(['legacy_author', 'kintone_comment_key']);
        });
    }
};
