<?php

namespace App\Console\Commands;

use App\Services\FlowFileService;
use Illuminate\Console\Command;

/**
 * アップロードされたがレコードが保存されなかったファイルを掃除する。
 *
 * 旧運用は temp_upload を7日で消す RemoveFile('temp') に相乗りしていたが、あれは
 * レコードに結び付いた本番のファイルまで同じ場所に置かれていた期間があり（テーブル項目の
 * ファイル列）、実データを消していた。pending という状態を持たせたので、消して良いものだけを
 * 消せる。
 */
class PurgeFlowPendingFiles extends Command
{
    protected $signature = 'flow:purge-pending-files {--days= : 保持日数（既定はFlowFileService::PENDING_TTL_DAYS）}';

    protected $description = 'カスタムアプリの未保存（pending）添付ファイルを削除する';

    public function handle(FlowFileService $files): int
    {
        // `?:` would swallow --days=0 ('0' is falsy), which is the value you reach for when
        // clearing everything by hand.
        $option = $this->option('days');
        $days = $option === null || $option === '' ? FlowFileService::PENDING_TTL_DAYS : max(0, (int) $option);
        $deleted = $files->purgePending($days);

        $this->info("未保存の添付ファイルを {$deleted} 件削除しました（{$days}日より前）。");

        return self::SUCCESS;
    }
}
