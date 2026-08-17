<?php

/**
 * 積立金が「生成はされているのに実績に反映されない」原因を特定する。
 *
 *   php debug_gate.php 2026-07
 *
 * ActualResultCalculationService::finalizeResult() の3条件のうち
 * どれで止まっているかを表示する（読み取り専用）。
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FreeeCredential;
use App\Services\ActualResultCalculationService;
use App\Services\Freee\FreeeActualResultService;

$month = $argv[1] ?? date('Y-m');
$credential = FreeeCredential::query()->where('active', true)
    ->where('status', FreeeCredential::STATUS_CONNECTED)->orderBy('id')->first();

/* --- 条件2: reserveAllocation が注入されているか --- */
$calc = app(ActualResultCalculationService::class);
$prop = new ReflectionProperty($calc, 'reserveAllocation');
$prop->setAccessible(true);
$injected = $prop->getValue($calc);

echo "=== {$month} 反映ゲート診断 ===".PHP_EOL.PHP_EOL;
echo '【条件2】reserveAllocation の注入: '
    .($injected !== null ? 'OK ('.get_class($injected).')' : '★ NULL ← ここが原因（積立金は一切生成されません）').PHP_EOL;

/* --- 実際に計算 --- */
$result = app(FreeeActualResultService::class)->calculateForMonth($credential, $month);
$file = $result['file'];

echo '【条件1】calculation_month: '.var_export($file['calculation_month'] ?? null, true)
    .(($file['calculation_month'] ?? null) === null ? '  ★ NULL ← ここが原因' : '  OK').PHP_EOL;

echo PHP_EOL.'【条件3】calculation_sources'.PHP_EOL;
foreach ($file['calculation_sources'] ?? [] as $k => $v) {
    $mark = '';
    if ($k === 'base_reserve_expenses') {
        $mark = $v === 'auto_calculated'
            ? '  OK（勤怠から生成する）'
            : '  ★ ここが原因（freee側に積立金があると判定 → 生成しない）';
    }
    printf("  %-28s %s%s\n", $k, $v, $mark);
}

echo PHP_EOL.'生成された積立金の行数: '.($file['generated_reserve_rows'] ?? 0).PHP_EOL;
echo '生成された積立金の合計: '.number_format((int) ($file['generated_reserve_total'] ?? 0)).'円'.PHP_EOL;

/* --- 実績に乗った金額 --- */
$sum = [];
foreach (['basic_bonus_reserve', 'paid_leave_reserve', 'welfare_reserve', 'refresh_reserve'] as $b) {
    $sum[$b] = 0;
    foreach ($result['departments'] as $d) {
        $sum[$b] += (int) ($d[$b] ?? 0);
    }
}
echo PHP_EOL.'実績に反映された積立金:'.PHP_EOL;
foreach ($sum as $b => $v) {
    printf("  %-24s %s円\n", $b, number_format($v));
}

/* --- 条件3が原因の場合、どの科目が引っかかったかを出す --- */
if (($file['calculation_sources']['base_reserve_expenses'] ?? '') !== 'auto_calculated') {
    echo PHP_EOL.'★ freee側の「積立金」科目が除外されずに取り込まれています。'.PHP_EOL;
    echo '  除外対象と判定されるか確認:'.PHP_EOL;
    foreach ($result['departments'] as $d) {
        foreach ($d['accounts'] ?? [] as $a) {
            $name = (string) $a['account_name'];
            if (in_array($a['bucket'] ?? '', ['basic_bonus_reserve', 'paid_leave_reserve', 'welfare_reserve', 'refresh_reserve'], true)
                && ($a['amount_source'] ?? '') === 'balance') {
                printf("    「%s」 部門=%s 金額=%s 除外判定=%s\n", $name, $d['department'],
                    number_format((int) $a['amount']),
                    $calc->isGeneratedAccountName($name) ? 'する(のに残っている?)' : '★ しない ← 名前が一致していない');
            }
        }
    }
}
