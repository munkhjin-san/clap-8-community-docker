<?php

/**
 * 勤怠の「プロジェクト名」と freee の「部門名」が一致しているかを確認する（読み取り専用）。
 *
 *   php debug_name_match.php 2026-07
 *
 * 名前が1文字でも違うと、積立金だけを持つ幽霊部門ができて、
 * freee由来の部門は積立金が空（—）のままになる。
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FreeeCredential;
use App\Services\ActualReserveAllocationService;
use App\Services\Freee\FreeeActualResultService;

$month = $argv[1] ?? date('Y-m');

$credential = FreeeCredential::query()->where('active', true)
    ->where('status', FreeeCredential::STATUS_CONNECTED)->orderBy('id')->first();

// 1) 勤怠側（積立金の配分先）
$alloc = app(ActualReserveAllocationService::class)->allocationsForMonth($month);
$timecardDepts = [];
foreach ($alloc['allocations'] as $a) {
    $timecardDepts[$a['department']] = ($timecardDepts[$a['department']] ?? 0) + $a['amount'];
}

// 2) freee側の部門名（＋連携済みプロジェクト名）を集める
$sectionNames = [];
foreach (app(App\Services\Freee\FreeeAccountingClient::class)->sections($credential, fresh: true) as $s) {
    $name = trim((string) ($s['name'] ?? ''));
    if ($name !== '') {
        $sectionNames[$name] = true;
    }
}
foreach (App\Models\ProjectRecord::query()->whereNotNull('freee_section_id')->pluck('name') as $n) {
    $n = trim((string) $n);
    if ($n !== '') {
        $sectionNames[$n] = true;
    }
}

// 別名で畳まれる名前もfreee側にあるとみなす
$calc = app(App\Services\ActualResultCalculationService::class);

echo "=== {$month} 名寄せチェック ===".PHP_EOL;
echo '勤怠側の配分先: '.count($timecardDepts).'部門 / freee側の部門: '.count($sectionNames).'件'.PHP_EOL;

$missing = [];
foreach ($timecardDepts as $name => $amount) {
    $found = false;
    foreach ($calc->freeeSectionNameCandidates($name) as $candidate) {
        if (isset($sectionNames[$candidate])) {
            $found = true;
            break;
        }
    }
    if (! $found) {
        $missing[$name] = $amount;
    }
}

echo PHP_EOL.'【要確認】勤怠のプロジェクト名がfreeeの部門に存在しない: '.count($missing).'件'.PHP_EOL;

if ($missing === []) {
    echo '  なし（すべての配分先がfreeeの部門と一致しています）'.PHP_EOL;
} else {
    arsort($missing);
    foreach ($missing as $n => $v) {
        printf("  ★ %-36s 積立金=%s円\n", mb_strimwidth($n, 0, 34), number_format($v));
        // 似た名前を提示（全角半角・空白を畳んで比較）
        $norm = fn (string $s) => mb_strtolower(preg_replace('/\s+/u', '',
            \Normalizer::normalize($s, \Normalizer::FORM_KC) ?: $s) ?? $s);
        foreach (array_keys($sectionNames) as $sec) {
            if ($norm($sec) === $norm($n)) {
                echo '        ↳ freee側に表記違いの候補: 「'.$sec.'」'.PHP_EOL;
            }
        }
    }
    echo PHP_EOL.'  → 積立金だけが別部門に乗り、freee由来の部門は空（—）になります。'.PHP_EOL;
    echo '    freeeの部門名か project_records.name のどちらかを合わせてください。'.PHP_EOL;
}
