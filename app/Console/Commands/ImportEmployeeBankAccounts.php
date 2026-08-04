<?php

namespace App\Console\Commands;

use App\Models\EmployeeBankAccount;
use App\Models\EmployeeBankAccountAccessLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 従業員の振込口座CSVを取り込む。
 *
 * CSVの「検索用キー」に users.name と同一表記の氏名が入っているので、突き合わせは完全一致。
 * 以前のCSVは氏名に空白が無く users.name は半角スペース入りだったため正規化して照合していたが、
 * その処理はもう不要（キー側が正規の表記を持っている）。前後の空白だけは落とす — Excel由来の
 * ファイルは末尾に空白が付くことがあり、それだけで一致しなくなるため。
 *
 * **既定はドライラン。** 実在の従業員の金融情報なので、まず何が起きるかを出し、--apply を付けた
 * ときだけ書き込む。
 *
 * 一致しなかった行は推測せずスキップして一覧に出す。口座番号は伏せ字でしか表示しない
 * （画面やCIログに平文を残さないため）。
 */
class ImportEmployeeBankAccounts extends Command
{
    protected $signature = 'bank-accounts:import
        {path=account_data1.csv : storage/app からの相対パス、または絶対パス}
        {--apply : 実際に書き込む（既定は変更内容の表示のみ）}
        {--encoding= : CSVの文字コード。未指定なら自動判定（UTF-8 / CP932）}';

    protected $description = '振込口座CSVを取り込み、口座情報を更新する（既定はドライラン）';

    private const REQUIRED_HEADERS = ['金融機関名', '支店名', '口座番号', '口座名義人', '口座名義人（フリガナ）', '検索用キー'];

    /** 口座番号。全角数字を半角にし、数字とハイフン以外を落とす。先頭0は保つので数値化しない。 */
    private function digits(?string $s): ?string
    {
        $s = trim((string) $s);
        if ($s === '') {
            return null;
        }
        if (class_exists(\Normalizer::class)) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_KC) ?: $s;
        }
        $s = preg_replace('/[^0-9\-]/u', '', $s) ?? '';

        return $s === '' ? null : $s;
    }

    private function text(?string $s): ?string
    {
        $s = trim((string) $s);

        return $s === '' ? null : $s;
    }

    /**
     * 取り込めなかった行を bank_import チャンネルへ残す。
     *
     * 画面の出力は実行した人しか見ないし、cronから回せば誰も見ない。取り込めなかった行は後から
     * 「なぜこの人の口座が入っていないのか」を追う必要があるので、行番号・キー・理由をログに残す。
     *
     * 口座番号は書かない。ログファイルは平文の出口にしない（reveal 経路だけが平文の出口という
     * 設計をログで崩さないため）。金融機関名・支店名は追跡に必要なので残す。
     */
    private function logSkip(int $line, string $reason, ?string $key, array $context = []): void
    {
        Log::channel('bank_import')->warning('取込スキップ', [
            'line' => $line,
            'key' => $key,
            'reason' => $reason,
            // ドライランでも記録する（失敗行の一覧を作る用途があるので）。ただし実際に書き込んだ回
            // と区別できないと誤読するので、どちらだったかを必ず残す。
            'dry_run' => ! (bool) $this->option('apply'),
            'file' => basename((string) $this->argument('path')),
        ] + $context);
    }

    /**
     * 行から列を取り出す。列名は必ず normalizeHeader() を通す。
     *
     * ヘッダ側だけ正規化して取り出し側で生の文字列を使うと、括弧を含む「口座名義人（フリガナ）」だけ
     * が全角/半角の食い違いで取れなくなる（他の列は括弧が無いので気づけない）。
     */
    private function cell(array $row, string $header): ?string
    {
        return $row[$this->normalizeHeader($header)] ?? null;
    }

    public function handle(): int
    {
        $path = $this->argument('path');
        $file = str_starts_with($path, '/') ? $path : storage_path('app/'.$path);
        if (! is_readable($file)) {
            $this->error('CSVが読めません: '.$file);

            return self::FAILURE;
        }

        $rows = $this->readCsv($file);
        if ($rows === null) {
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $plan = ['create' => [], 'update' => [], 'blank' => [], 'unmatched' => [], 'ambiguous' => [], 'warn' => [], 'lens' => []];
        $seen = [];

        foreach ($rows as $i => $r) {
            $line = $i + 2;   // ヘッダ込みの行番号
            $key = $this->text($this->cell($r, '検索用キー'));
            if ($key === null) {
                $plan['warn'][] = "L{$line}: 検索用キーが空のためスキップ";
                $this->logSkip($line, '検索用キーが空', null);

                continue;
            }

            if (isset($seen[$key])) {
                $plan['warn'][] = "L{$line} {$key}: CSV内でキーが重複（後の行を無視）";
                $this->logSkip($line, 'CSV内でキーが重複', $key);

                continue;
            }
            $seen[$key] = true;

            // 完全一致で1人に決まることを要求する。複数出たら推測しない。
            $candidates = User::where('name', $key)->get(['id', 'name', 'retire']);
            if ($candidates->count() > 1) {
                $plan['ambiguous'][] = "L{$line} {$key} → #".$candidates->pluck('id')->implode(', #');
                $this->logSkip($line, '同名ユーザーが複数', $key, ['candidate_user_ids' => $candidates->pluck('id')->all()]);

                continue;
            }
            $user = $candidates->first();
            if (! $user) {
                $plan['unmatched'][] = "L{$line} {$key}";
                $this->logSkip($line, 'users.name と完全一致しない', $key);

                continue;
            }
            // 退職者は除外せず取り込むが、気づけるように出す（退職後もデータは保持する方針）
            if ((int) $user->retire !== 0) {
                $plan['warn'][] = "L{$line} {$key}: 退職者として登録されています（#{$user->id}）";
            }

            $fields = [
                'account_holder' => $this->text($this->cell($r, '口座名義人')),
                'account_holder_kana' => $this->text($this->cell($r, '口座名義人（フリガナ）')),
                'bank_name' => $this->text($this->cell($r, '金融機関名')),
                'branch_name' => $this->text($this->cell($r, '支店名')),
            ];
            $number = $this->digits($this->cell($r, '口座番号'));

            // 口座情報が何も無い行は触らない（空の行を作らないし、既存の値も消さない）
            if ($number === null && ! array_filter($fields, fn ($v) => $v !== null)) {
                $plan['blank'][] = "L{$line} {$key}";
                $this->logSkip($line, '口座情報が空', $key);

                continue;
            }

            if ($number !== null) {
                // 他行の口座番号は7桁が標準。ゆうちょは桁数が一定でないが、極端に短いものはおかしい。
                // 短い＝Excelが先頭0を落とした疑い（列を文字列にせずに書き出すと0が消える）。
                $isYucho = $fields['bank_name'] !== null && str_contains($fields['bank_name'], 'ゆうちょ');
                $len = strlen(preg_replace('/\D/', '', $number) ?? '');
                $floor = $isYucho ? 6 : 7;
                if ($len > 0 && $len < $floor) {
                    $plan['warn'][] = "L{$line} {$key}: 口座番号が{$len}桁（{$fields['bank_name']}／通常"
                        .($isYucho ? '6〜8' : '7').'桁）。Excelが先頭0を落としていないか確認';
                }
                $plan['lens'][$len] = ($plan['lens'][$len] ?? 0) + 1;
            }

            $existing = EmployeeBankAccount::withTrashed()->where('user_id', $user->id)->first();
            $plan[$existing ? 'update' : 'create'][] = "{$key} (#{$user->id}) ".($fields['bank_name'] ?? '—')
                .' '.($number !== null ? '****'.substr($number, -4) : '番号なし');

            if (! $apply) {
                continue;
            }

            DB::transaction(function () use ($user, $fields, $number, $existing) {
                $attrs = $fields;
                // CSVに番号が無い行では既存の番号を残す（他の項目だけ直す運用と同じ約束）
                if ($number !== null) {
                    $attrs['account_number'] = $number;
                }
                $attrs['updated_by_user_id'] = null;   // import: 人ではない
                $attrs['created_by_user_id'] = $existing?->created_by_user_id;

                $account = EmployeeBankAccount::withTrashed()->firstOrNew(['user_id' => $user->id]);
                $account->fill($attrs);
                $account->deleted_at = null;           // 論理削除済みなら復活させる
                $account->save();

                EmployeeBankAccountAccessLog::record(null, $user->id, $existing ? 'update' : 'create');
            });
        }

        $this->report($plan, $apply, count($rows));

        return self::SUCCESS;
    }

    /** ヘッダ表記のゆれ（全角/半角括弧など）を吸収するための正規化。 */
    private function normalizeHeader(string $h): string
    {
        $h = trim($h);

        return class_exists(\Normalizer::class) ? (\Normalizer::normalize($h, \Normalizer::FORM_KC) ?: $h) : $h;
    }

    /** @return array<int,array<string,string>>|null */
    private function readCsv(string $file): ?array
    {
        $raw = file_get_contents($file);
        if ($raw === false) {
            $this->error('CSVを読み込めませんでした。');

            return null;
        }

        // 文字コードは自動判定。Excel由来はCP932だが、書き出し方によってはUTF-8のこともある。
        $encoding = $this->option('encoding');
        if (! $encoding) {
            $encoding = mb_check_encoding($raw, 'UTF-8') ? 'UTF-8' : 'CP932';
            $this->line('文字コード: '.$encoding.'（自動判定）');
        }
        if (strtoupper((string) $encoding) !== 'UTF-8') {
            $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
        }
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);   // BOM

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $header = null;
        $out = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = str_getcsv($line, ',', '"', '\\');
            if ($header === null) {
                $header = array_map(fn ($h) => $this->normalizeHeader((string) $h), $cells);

                continue;
            }
            $row = [];
            foreach ($header as $ci => $h) {
                $row[$h] = $cells[$ci] ?? '';
            }
            $out[] = $row;
        }

        $missing = array_values(array_filter(
            self::REQUIRED_HEADERS,
            fn ($h) => ! in_array($this->normalizeHeader($h), $header ?? [], true)
        ));
        if ($missing) {
            $this->error('CSVのヘッダに次の列が見つかりません: '.implode('、', $missing));
            $this->line('読めたヘッダ: '.implode(' | ', $header ?? []));
            $this->line('文字コードが違う場合は --encoding=CP932 などを指定してください。');

            return null;
        }

        return $out;
    }

    private function report(array $plan, bool $apply, int $total): void
    {
        $verb = $apply ? '実行しました' : '実行される予定です（ドライラン）';
        $this->newLine();
        $this->line("CSV {$total}行を処理。以下が{$verb}。");
        $this->newLine();
        $this->table(
            ['区分', '件数'],
            [
                ['新規登録', count($plan['create'])],
                ['更新', count($plan['update'])],
                ['口座情報が空（スキップ）', count($plan['blank'])],
                ['氏名一致なし（スキップ）', count($plan['unmatched'])],
                ['候補が複数（スキップ）', count($plan['ambiguous'])],
                ['警告', count($plan['warn'])],
            ]
        );

        foreach ([
            'unmatched' => '検索用キーが users.name と完全一致しなかった行（表記ゆれ・未登録）',
            'ambiguous' => '同名のユーザーが複数いた行（自動では決めません）',
            'warn' => '警告',
        ] as $k => $title) {
            if (! $plan[$k]) {
                continue;
            }
            $this->newLine();
            $this->line('--- '.$title.' ---');
            foreach ($plan[$k] as $l) {
                $this->line('  '.$l);
            }
        }

        if ($this->getOutput()->isVerbose()) {
            foreach (['create' => '新規登録', 'update' => '更新'] as $k => $title) {
                if (! $plan[$k]) {
                    continue;
                }
                $this->newLine();
                $this->line('--- '.$title.' ---');
                foreach ($plan[$k] as $l) {
                    $this->line('  '.$l);
                }
            }
        } else {
            $this->newLine();
            $this->line('※ 対象者の一覧は -v を付けると表示されます（口座番号は下4桁のみ）。');
        }

        if ($plan['lens']) {
            ksort($plan['lens']);
            $this->newLine();
            $this->line('--- 口座番号の桁数分布（7桁が標準。極端に短いものは先頭0の欠落が疑われる） ---');
            foreach ($plan['lens'] as $len => $n) {
                $this->line('  '.$len.'桁: '.$n.'件');
            }
        }

        if (! $apply) {
            $this->newLine();
            $this->warn('ドライランです。実際に書き込むには --apply を付けて実行してください。');
        }
    }
}
