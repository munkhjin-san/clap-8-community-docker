<?php

/**
 * freee台帳と freee 実データのズレを確認する（読み取り専用）。
 *
 *   php debug_freee_sync_state.php 2026-07
 *
 * 「freeeは既に最新です」と出るのに freee 側に無い、という状態を切り分ける。
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FreeeCredential;
use App\Models\FreeeJournalPost;
use App\Services\Freee\FreeeAccountingClient;
use App\Services\Freee\FreeeJournalPostService;
use Carbon\Carbon;

$month = $argv[1] ?? date('Y-m');
$credential = FreeeCredential::query()->where('active', true)
    ->where('status', FreeeCredential::STATUS_CONNECTED)->orderBy('id')->first();

if (! $credential) {
    exit('連携済みのfreee設定がありません'.PHP_EOL);
}

$client = app(FreeeAccountingClient::class);
echo '事業所ID: '.(config('services.freee.company_id') ?: $credential->company_id).PHP_EOL;

/* 1) 修正が入っているか */
$hasFix = method_exists($client, 'manualJournalExists');
echo '存在チェックの修正: '.($hasFix ? '入っている' : '★ 未反映（デプロイが必要）').PHP_EOL;

/* 2) 台帳 vs freee */
echo PHP_EOL.'=== 台帳の伝票が freee に実在するか ==='.PHP_EOL;
$posts = FreeeJournalPost::query()
    ->whereDate('target_month', Carbon::createFromFormat('Y-m', $month)->startOfMonth())
    ->orderBy('bucket')->get();

if ($posts->isEmpty()) {
    echo '  台帳に記録なし（まだ一度も送信していない月）'.PHP_EOL;
}

foreach ($posts as $p) {
    $exists = $hasFix ? $client->manualJournalExists($credential, $p->freee_journal_id) : null;
    printf("  %-28s 伝票=%-12s 台帳金額=%-12s %s\n", $p->bucket, $p->freee_journal_id,
        number_format($p->amount),
        $exists === null ? '(確認不可)' : ($exists ? '実在する' : '★ freeeに無い → 再送信で作り直されます'));
}

/* 3) freee の実際の値 */
echo PHP_EOL.'=== freee 側の当月の値（GLOWD科目）==='.PHP_EOL;
$pl = $client->trialPl($credential, Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString(),
    Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString());

$calc = app(App\Services\ActualResultCalculationService::class);
foreach ($pl['balances'] ?? [] as $line) {
    if (filter_var($line['total_line'] ?? false, FILTER_VALIDATE_BOOL)) {
        continue;
    }
    $name = trim((string) ($line['account_item_name'] ?? ''));
    if ($name === '' || ! $calc->isGeneratedAccountName($name)) {
        continue;
    }
    $delta = (int) ($line['closing_balance'] ?? 0) - (int) ($line['opening_balance'] ?? 0);
    if ($delta !== 0) {
        printf("  %-28s %s\n", mb_strimwidth($name, 0, 26), number_format($delta));
    }
}

/* 4) いま GLOWD が計算している値 */
echo PHP_EOL.'=== いま GLOWD が送ろうとしている値（ドライラン）==='.PHP_EOL;
$out = app(FreeeJournalPostService::class)->postForMonth($credential, $month, true, null);
foreach ($out['results'] as $r) {
    printf("  %-28s %-10s %-12s %s\n", $r['label'], $r['action'],
        number_format((int) ($r['amount'] ?? 0)), $r['reason'] ?? '');
}
echo PHP_EOL.'※ freee側の値と台帳金額が一致していれば「unchanged」は正常です。'.PHP_EOL;
