<?php

/**
 * 積立金が計算されない原因を切り分ける読み取り専用スクリプト。
 *
 *   php artisan  は不要。プロジェクト直下に置いて:
 *     php debug_reserve_check.php 2026-07
 *
 * どの段階で0件になっているかを上から順に表示する。書き込みは一切しない。
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Infrastructure\Kintone\KintoneClient;
use App\Models\timecardRecord;
use App\Services\ActualReserveAllocationService;
use App\Services\KintoneCostMasterSyncService;
use App\Support\EmployeeCode;
use Illuminate\Support\Facades\DB;

$month = $argv[1] ?? date('Y-m');
[$year, $mon] = array_map('intval', explode('-', $month));

echo str_repeat('=', 70).PHP_EOL;
echo "積立金 診断: {$month}".PHP_EOL;
echo str_repeat('=', 70).PHP_EOL;

/* ---------- 1) 勤怠レコード ---------- */
$records = timecardRecord::query()
    ->whereYear('day', $year)->whereMonth('day', $mon)
    ->with(['user:id,name,user_code,position_id', 'department:id,name', 'project_segments'])
    // work_group_id を取らないと department リレーションが必ず null になる（本体と同じ select にする）
    ->get(['id', 'user_id', 'day', 'work_group_id', 'work_time']);

echo PHP_EOL.'【1】timecard_records: '.$records->count().'件'.PHP_EOL;

if ($records->isEmpty()) {
    echo '  ★ ここが原因。対象月の勤怠が1件も無いので、積立金は必ず0になります。'.PHP_EOL;
    echo '    確認: SELECT COUNT(*) FROM timecard_records WHERE YEAR(day)='.$year.' AND MONTH(day)='.$mon.';'.PHP_EOL;
    exit(0);
}

$noUser = $records->filter(fn ($r) => $r->user === null);
$withSeg = $records->filter(fn ($r) => ($r->project_segments?->count() ?? 0) > 0);
$noDept = $records->filter(fn ($r) => ($r->project_segments?->count() ?? 0) === 0 && $r->department === null);

echo '  ユーザー未解決(user_id が users に無い): '.$noUser->count().'件'.PHP_EOL;
echo '  project_segments あり: '.$withSeg->count().'件 / なし(部門フォールバック): '.($records->count() - $withSeg->count()).'件'.PHP_EOL;
echo '  部門もセグメントも無い(捨てられる): '.$noDept->count().'件'.PHP_EOL;

$users = $records->pluck('user')->filter()->unique('id');
echo '  対象ユーザー数: '.$users->count().PHP_EOL;
echo '  うち user_code 空: '.$users->filter(fn ($u) => trim((string) $u->user_code) === '')->count().PHP_EOL;
echo '  うち position_id 未設定: '.$users->filter(fn ($u) => $u->position_id === null)->count()
    .'  ← null は積立対象外になります'.PHP_EOL;

/* ---------- 2) 役職による対象判定 ---------- */
$limits = ['基本賞与' => 11, '福利厚生' => 12, 'リフレッシュ' => 12, '有給' => 13];
echo PHP_EOL.'【2】役職(position_id)による対象者数'.PHP_EOL;
foreach ($limits as $label => $limit) {
    $ok = $users->filter(fn ($u) => $u->position_id !== null
        && ((int) $u->position_id <= $limit || (int) $u->position_id === 16));
    printf("  %-10s (position_id<=%d, 16可) : %d人\n", $label, $limit, $ok->count());
}

/* ---------- 3) Kintone app 96 ---------- */
echo PHP_EOL.'【3】Kintone app '.KintoneCostMasterSyncService::APP_PEOPLE_COSTS.' (人件費)'.PHP_EOL;
$kintone = app(KintoneClient::class);
$peopleByCode = [];

try {
    $fields = array_keys($kintone->getAppFields(KintoneCostMasterSyncService::APP_PEOPLE_COSTS));
    echo '  フィールド取得: OK ('.count($fields).'項目)'.PHP_EOL;

    // 本体と同じく「候補のどれかが当たればOK」で判定する（1つの名前だけ見ると誤検知する）
    $needles = [
        '基本給' => ['基本給'],
        '役職手当' => ['役職手当'],
        '有給引当金' => ['有給引当金', '有休引当金', '有給休暇引当金'],
        '福利厚生費等引当金' => ['福利厚生費等引当金', '福利厚生引当金'],
        'リフレッシュ補助引当金' => ['リ補引当金', 'リフレッシュ補助金引当金', 'リフレッシュ補助引当金', 'リフレッシュ引当金', 'リフレッシュ補助積立金'],
        '社員コード' => ['社員コード'],
    ];

    foreach ($needles as $label => $candidates) {
        $hit = [];
        foreach ($candidates as $needle) {
            $hit = array_merge($hit, array_values(array_filter($fields, fn ($f) => str_contains($f, $needle))));
        }
        printf("    %-24s %s\n", $label, $hit ? implode(' , ', array_slice(array_unique($hit), 0, 3)) : '★ 見つからない');
    }

    $records96 = $kintone->getAllRecords(KintoneCostMasterSyncService::APP_PEOPLE_COSTS, '', [], 500);
    echo '  レコード取得: '.count($records96).'件'.PHP_EOL;

    foreach ($records96 as $rec) {
        foreach (['社員コード数値', '社員コード'] as $f) {
            $v = $rec[$f]['value'] ?? ($rec[$f] ?? null);
            if (is_string($v) && trim($v) !== '') {
                $peopleByCode[EmployeeCode::normalize($v)] = true;
                break;
            }
        }
    }
    echo '  社員コードを持つレコード: '.count($peopleByCode).'件'.PHP_EOL;
} catch (\Throwable $e) {
    echo '  ★ Kintone接続に失敗: '.mb_substr($e->getMessage(), 0, 200).PHP_EOL;
    echo '    → ここが原因。prodのKintone認証(config app.kintone_*)を確認してください。'.PHP_EOL;
}

/* ---------- 4) 突き合わせ ---------- */
if ($peopleByCode !== []) {
    echo PHP_EOL.'【4】勤怠ユーザー ⇔ Kintone 社員コードの突合'.PHP_EOL;
    $matched = $unmatched = [];
    foreach ($users as $u) {
        $code = EmployeeCode::normalize((string) $u->user_code);
        ($code !== '' && isset($peopleByCode[$code])) ? $matched[] = $u : $unmatched[] = $u;
    }
    echo '  一致: '.count($matched).'人 / 不一致: '.count($unmatched).'人'.PHP_EOL;
    foreach (array_slice($unmatched, 0, 10) as $u) {
        printf("    × %-12s user_code=%-10s → 正規化=%s\n", $u->name, var_export($u->user_code, true),
            EmployeeCode::normalize((string) $u->user_code));
    }
    if ($matched === []) {
        echo '  ★ ここが原因。社員コードが1件も一致していません。'.PHP_EOL;
    }
}

/* ---------- 5) 実際の計算結果 ---------- */
echo PHP_EOL.'【5】ActualReserveAllocationService の出力'.PHP_EOL;
$result = app(ActualReserveAllocationService::class)->allocationsForMonth($month);
$stats = $result['stats'];
foreach ($stats as $k => $v) {
    printf("  %-28s %s\n", $k, number_format((int) $v));
}
echo '  警告: '.count($result['warnings']).'件'.PHP_EOL;
foreach (array_slice($result['warnings'], 0, 5) as $w) {
    echo '    - '.$w.PHP_EOL;
}

if ((int) ($stats['generated_rows'] ?? 0) === 0) {
    echo PHP_EOL.'  ★ 生成0件。上の【1】〜【4】で ★ が付いた段階が原因です。'.PHP_EOL;
} else {
    echo PHP_EOL.'  → 生成できています。部門名がfreeeのプロジェクトと一致しているか確認してください。'.PHP_EOL;
    $byDept = [];
    foreach ($result['allocations'] as $a) {
        $byDept[$a['department']] = ($byDept[$a['department']] ?? 0) + $a['amount'];
    }
    arsort($byDept);
    foreach (array_slice($byDept, 0, 10, true) as $d => $amt) {
        printf("    %-34s %s円\n", mb_strimwidth($d, 0, 32), number_format($amt));
    }
}
