<?php

namespace Tests\Feature;

use App\Models\FlowRecordFile;
use Tests\TestCase;

/**
 * 保管パスの規則。DBは使わない。
 *
 * 直した問題そのものを固定する：レコードIDのフォルダを1つの親に並べていたので、レコードが増えると
 * 兄弟フォルダが何十万にもなっていた。アプリで分け、さらに1000件ごとの中間フォルダを挟む。
 */
class FlowFilePathTest extends TestCase
{
    public function test_paths_are_split_by_app_and_bucketed_by_thousand(): void
    {
        $this->assertSame('flow_files/15/1000/1277/9.pdf', FlowRecordFile::pathFor(15, 1277, 9, 'pdf'));
        // 境界: 999 → 0番台、1000 → 1000番台
        $this->assertSame('flow_files/7/0/999/1.png', FlowRecordFile::pathFor(7, 999, 1, 'png'));
        $this->assertSame('flow_files/7/1000/1000/1.png', FlowRecordFile::pathFor(7, 1000, 1, 'png'));
        $this->assertSame('flow_files/7/2000000/2000450/1.png', FlowRecordFile::pathFor(7, 2000450, 1, 'png'));
    }

    /** 1レコード分がひとつのフォルダに収まる＝削除は deleteDirectory 一回で済む。 */
    public function test_record_directory_matches_the_file_path_prefix(): void
    {
        $dir = FlowRecordFile::recordDirFor(15, 1277);

        $this->assertSame('flow_files/15/1000/1277', $dir);
        $this->assertStringStartsWith($dir.'/', FlowRecordFile::pathFor(15, 1277, 42, 'pdf'));
    }

    /** 未保存の置き場はアプリごと。掃除されるので枝分かれは増えない。 */
    public function test_pending_path_is_per_app(): void
    {
        $this->assertSame('flow_files/15/_pending/9.txt', FlowRecordFile::pendingPathFor(15, 9, 'txt'));
    }

    /**
     * 拡張子はパスに入るので必ず絞る。元のファイル名は disk には使わない
     * （日本語・記号・長さでパスが壊れる、ディレクトリ遡上も防ぐ）。
     */
    public function test_extension_is_sanitised_into_the_path(): void
    {
        $this->assertSame('flow_files/1/0/2/3.pdf', FlowRecordFile::pathFor(1, 2, 3, 'PDF'));
        // 遡上や区切り文字を混ぜても素の英数字しか残らない
        $this->assertSame('flow_files/1/0/2/3.png', FlowRecordFile::pathFor(1, 2, 3, '../../p n g!'));
        // 拡張子なしはドットも付けない
        $this->assertSame('flow_files/1/0/2/3', FlowRecordFile::pathFor(1, 2, 3, null));
        $this->assertSame('flow_files/1/0/2/3', FlowRecordFile::pathFor(1, 2, 3, '!!!'));
    }

    /** 配信は必ず権限を見るルート経由。/cdn/ の生パスは使わない。 */
    public function test_url_points_at_the_permission_checked_route(): void
    {
        $file = new FlowRecordFile;
        $file->id = 42;

        $this->assertSame('/flow_file/42', $file->url());
    }

    /** value_json には URL を持たせない（保管場所を変えるたびにデータ移行が要る形にしない）。 */
    public function test_value_payload_carries_no_url(): void
    {
        $file = new FlowRecordFile(['name' => 'a.pdf', 'extension' => 'pdf', 'size' => 10, 'uploaded_by' => 3]);
        $file->id = 7;

        $payload = $file->valuePayload();

        $this->assertSame(['id', 'name', 'extension', 'mime_type', 'size', 'user_id'], array_keys($payload));
        $this->assertArrayNotHasKey('url', $payload);
        $this->assertArrayNotHasKey('stored', $payload);
    }
}
