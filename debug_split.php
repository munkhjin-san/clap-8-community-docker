<?php

/**
 * 「freee由来の売上」と「勤怠由来の積立金」が別々の部門に分かれていないかを見る。
 *
 *   php debug_split.php 2026-07
 *
 * 表記ゆれ（全角/半角など）で名前が1文字でも違うと、
 * freee側の部門は積立金が空（—）、勤怠側の名前で幽霊部門ができる。
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FreeeCredential;
use App\Services\Freee\FreeeActualResultService;

$month = $argv[1] ?? date('Y-m');
$credential = FreeeCredential::query()->where('active', true)
    ->where('status', FreeeCredential::STATUS_CONNECTED)->orderBy('id')->first();

$result = app(FreeeActualResultService::class)->calculateForMonth($credential, $month);

$norm = function (string $s): string {
    $s = \Normalizer::normalize($s, \Normalizer::FORM_KC) ?: $s;

    return mb_strtolower(preg_replace('/[\s　]+/u', '', $s) ?? $s);
};

$rows = [];
foreach ($result['departments'] as $d) {
    $reserves = 0;
    foreach (['basic_bonus_reserve', 'paid_leave_reserve', 'welfare_reserve', 'refresh_reserve'] as $k) {
        $reserves += (int) ($d[$k] ?? 0);
    }
    $rows[] = [
        'name' => $d['department'],
        'key' => $norm($d['department']),
        'sales' => (int) $d['external_sales'],
        'sga' => (int) $d['ordinary_expenses'],
        'reserves' => $reserves,
    ];
}

// 正規化して同じになる部門＝表記ゆれで割れている可能性
$groups = [];
foreach ($rows as $r) {
    $groups[$r['key']][] = $r;
}

echo "=== {$month} 部門の分裂チェック ===".PHP_EOL;
$split = array_filter($groups, fn ($g) => count($g) > 1);

if ($split) {
    echo PHP_EOL.'★ 表記ゆれで割れている部門: '.count($split).'組'.PHP_EOL;
    foreach ($split as $g) {
        echo '  --- 同一とみなせる名前 ---'.PHP_EOL;
        foreach ($g as $r) {
            printf("    「%s」 売上=%s 販管費=%s 積立金=%s\n", $r['name'],
                number_format($r['sales']), number_format($r['sga']), number_format($r['reserves']));
        }
    }
} else {
    echo PHP_EOL.'表記ゆれによる分裂はありません。'.PHP_EOL;
}

// freeeの数字はあるのに積立金ゼロ / その逆
$noReserve = array_filter($rows, fn ($r) => ($r['sales'] > 0 || $r['sga'] > 0) && $r['reserves'] === 0);
$onlyReserve = array_filter($rows, fn ($r) => $r['sales'] === 0 && $r['sga'] === 0 && $r['reserves'] > 0);

echo PHP_EOL.'【A】freeeの数字はあるが積立金がゼロ: '.count($noReserve).'部門'.PHP_EOL;
usort($noReserve, fn ($a, $b) => $b['sales'] <=> $a['sales']);
foreach (array_slice($noReserve, 0, 10) as $r) {
    printf("    %-34s 売上=%s\n", mb_strimwidth($r['name'], 0, 32), number_format($r['sales']));
}

echo PHP_EOL.'【B】積立金だけがある（freeeに実績なし）: '.count($onlyReserve).'部門'.PHP_EOL;
usort($onlyReserve, fn ($a, $b) => $b['reserves'] <=> $a['reserves']);
foreach (array_slice($onlyReserve, 0, 10) as $r) {
    printf("    %-34s 積立金=%s\n", mb_strimwidth($r['name'], 0, 32), number_format($r['reserves']));
}

echo PHP_EOL.'※【A】と【B】に似た名前のペアがあれば、それが分裂しています。'.PHP_EOL;
