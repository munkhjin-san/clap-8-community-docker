<?php

namespace App\Console\Commands;

use App\Infrastructure\Kintone\KintoneClient;
use App\Models\PartnerRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Normalizer;

/**
 * kintoneアプリ「取引先」(118) から取引先マスタを取り込む一度きりの移行コマンド。
 *
 * 画面にボタンは置かない。継続的な同期ではなく初期データの投入が目的で、
 * 誤って再実行されると手で直した内容を上書きしてしまうため。
 *
 * 取り込まないもの:
 *  - 「取引先ID」(大文字ID・例 12176776) … freeeの取引先ID。**本番freeeのID**であり、
 *    この環境が繋いでいるテスト事業所とは一致しない。取り込むと存在しない相手と
 *    紐付いた状態になるため、すべて未連携（freee_partner_id = null）で取り込む。
 *    本番freeeに向けるときは、この項目を freee_partner_id に写すよう変更して再実行する。
 *    （「取引先id」＝小文字id は kintone 側の連番で、freeeとは無関係。混同しないこと）
 *  - 電話番号 … アプリ118のトップレベルに項目が無い。
 */
class ImportPartnersFromKintone extends Command
{
    protected $signature = 'partners:import-kintone
        {--app=118 : kintoneのアプリID}
        {--fresh : 取り込み前に既存の取引先を全件削除する}
        {--dry-run : 保存せず件数と内訳だけ表示する}';

    protected $description = 'kintoneアプリ「取引先」から取引先マスタを取り込む（一度きりの移行用）';

    /** 我々の列 => kintoneのフィールドコード。 */
    private const FIELD_MAP = [
        'name' => '会社名',                       // 通称1（カタカナの場合は大文字）
        'long_name' => '文字列__1行_',            // 会社名（正式）
        'code' => '文字列__1行__4',               // 企業コード
        'corporate_number' => '文字列__1行__10',  // 法人番号
        'invoice_registration_number' => '文字列__1行__8', // 適格請求書番号
        'postal_code' => '郵便番号',
        'address_2' => '住所2',                   // ビル名
        'contact_name' => '取引先担当者_0',
        'contact_position' => '文字列__1行__1',   // 役職
        'email' => 'リンク_0',                    // メールアドレス
        'website' => '文字列__1行__0',            // ホームページURL
        'isms_registration_number' => 'ISMS認証登録番号',
        'privacy_mark_number' => 'プライバシーマーク許諾番号',
    ];

    private const ENTITY_TYPE_MAP = [
        '法人' => 'corporate',
        '個人' => 'individual',
    ];

    private const TRANSACTION_CATEGORY_MAP = [
        'クライアント' => 'client',
        'パートナー企業' => 'partner',
        '物件_車両_駐車場' => 'property_vehicle_parking',
        '買掛先' => 'payable',
        'その他' => 'other',
    ];

    /** チェックボックスの選択肢（kintoneの文言）=> 我々の設問キー。 */
    private const INFO_SECURITY_MAP = [
        '情報セキュリティに関する基本的な考え方・方針を定めるとともに、遵守すべきセキュリティ水準についてガイドライン等で示している' => 'is_01',
        '情報の取り扱いに関する規程等への違反に対し、懲戒等の処分に関する手続きを定めている' => 'is_02',
        '情報セキュリティに関するインシデントが発生した際の検知・報告・通報の仕組みを整えており、社外公表やユーザー対応等、事故発生からクロージングまでの対応がルール化されている' => 'is_03',
        '情報管理責任者が任命され、役割を責任が明確化されている' => 'is_04',
        '業務に従事する全社員を対象として、情報セキュリティに関する研修を定期的に実施しており、実施のタイミングや内容についても必要に応じて見直しを行っている' => 'is_05',
        '機密情報が記載された書類は関係者以外に振れることがないよう、施錠された場所へ保管している' => 'is_06',
        '機密情報を社外へ持ち出す場合、持出記録を行っている' => 'is_07',
        '不要になった情報の速やかな廃棄を行うためのルール・仕組がある' => 'is_08',
        'ファイルサーバー等に機密情報を保存する場合、パスワード設定等により暗号化している' => 'is_09',
        '業務で利用する端末および外部記録媒体について、保有状況・利用（払い出し）状況を管理・記録している' => 'is_10',
        '機密情報を保存する端末及び外部記録媒体は関係者以外が容易に持ち出すことのできないよう対策がなされている' => 'is_11',
    ];

    private const LABOR_CONTRACT_MAP = [
        '労働基準法の定める周知義務に準じている' => 'lc_01',
        '所属社員の身元保証人の把握をしている' => 'lc_02',
        '労働条件の明示を労働条件通知書の交付により実施している' => 'lc_03',
        '３６協定に基づく労使協定を締結している' => 'lc_04',
        '健康診断の定期受診をしている' => 'lc_05',
        '賃金未払い、遅延またはサービス残業など労働者の不利益となる事象を発生させたことがある' => 'lc_06',
        '個人の責に帰すべき事由による損害賠償請求において、企業が個人に対して賠償責任を問う旨の内容が就業規則に記載されているか' => 'lc_07',
        '業務遂行者に対して、本業務遂行に関わる内容を直接ヒアリングすることがあります。' => 'lc_08',
    ];

    /** 住所の先頭から都道府県を切り出すための対応表（freeeのコードは0〜46）。 */
    private const PREFECTURES = [
        '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
        '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
        '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
        '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
        '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
        '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県',
    ];

    /** 形式が合わず取り込まなかった値。黙って落とさず最後に一覧で出す。 */
    private array $invalid = [];

    public function handle(KintoneClient $kintone): int
    {
        $appId = (int) $this->option('app');
        $dryRun = (bool) $this->option('dry-run');

        $codes = array_values(array_unique(array_merge(
            array_values(self::FIELD_MAP),
            ['$id', 'ラジオボタン', '取引区分', '住所1', 'チェックボックス_0', '情報管理に関する質問', '労働契約に関する質問', '作成日時', '更新日時'],
        )));

        $this->info("kintoneアプリ {$appId} から取引先を読み込みます…");
        $records = $kintone->getAllRecords($appId, '', $codes);
        $this->info('取得: '.count($records).'件');

        $rows = [];
        $skipped = [];
        $seen = [];

        foreach ($records as $record) {
            $name = $this->trimmed($record, '会社名');

            // 通称1が空の行は取り込めない（名前が突き合わせの実質キーのため）。
            if ($name === '') {
                $skipped[] = ['id' => $this->raw($record, '$id'), 'reason' => '通称1が空'];

                continue;
            }

            // 同名が来たら後勝ちにせず、先に出た方を残して記録する。
            $key = $this->normalizeName($name);
            if (isset($seen[$key])) {
                $skipped[] = ['id' => $this->raw($record, '$id'), 'reason' => "通称1が重複: {$name}"];

                continue;
            }
            $seen[$key] = true;

            $rows[] = $this->buildRow($record, $name);
        }

        $this->table(
            ['取り込み対象', '除外'],
            [[count($rows), count($skipped)]],
        );

        foreach (array_slice($skipped, 0, 10) as $s) {
            $this->warn("  除外 record {$s['id']}: {$s['reason']}");
        }
        if (count($skipped) > 10) {
            $this->warn('  ほか'.(count($skipped) - 10).'件');
        }

        $withPref = count(array_filter($rows, fn ($r) => $r['prefecture_code'] !== null));
        $ended = count(array_filter($rows, fn ($r) => $r['available'] === false));
        $this->line("  都道府県を住所から判定: {$withPref}件");
        $this->line("  契約終了フラグにより使用不可: {$ended}件");

        if ($this->invalid !== []) {
            $this->newLine();
            $this->warn('形式が合わず取り込まなかった項目（レコード自体は取り込み済み）:');
            foreach ($this->invalid as $message) {
                $this->warn('  '.$message);
            }
        }

        if ($dryRun) {
            $this->info('--dry-run のため保存しませんでした。');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            if ($this->option('fresh')) {
                $deleted = PartnerRecord::withTrashed()->count();
                DB::table('project_partners')->delete();
                PartnerRecord::withTrashed()->forceDelete();
                $this->warn("既存の取引先 {$deleted}件と紐付けを削除しました。");
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                PartnerRecord::query()->insert($chunk);
            }
        });

        $this->info('取り込み完了: '.PartnerRecord::count().'件');

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function buildRow(array $record, string $name): array
    {
        $row = ['name' => $name];

        foreach (self::FIELD_MAP as $column => $code) {
            if ($column === 'name') {
                continue;
            }
            $value = $this->trimmed($record, $code);
            $row[$column] = $value === '' ? null : $value;
        }

        // 番号系は表記ゆれを正す。ハイフンや〒付きのまま入れると、
        // 画面から編集して保存したときにこちらの入力規則で弾かれてしまう。
        $row['postal_code'] = $this->normalizePostalCode($row['postal_code']);
        $row['corporate_number'] = $this->normalizeCorporateNumber($row['corporate_number'], $name);
        $row['invoice_registration_number'] = $this->normalizeInvoiceNumber($row['invoice_registration_number'], $name);

        [$prefectureCode, $address1] = $this->splitPrefecture($this->trimmed($record, '住所1'));
        $row['prefecture_code'] = $prefectureCode;
        $row['address_1'] = $address1;

        $row['entity_type'] = self::ENTITY_TYPE_MAP[$this->trimmed($record, 'ラジオボタン')] ?? null;
        $row['transaction_category'] = self::TRANSACTION_CATEGORY_MAP[$this->trimmed($record, '取引区分')] ?? null;

        // 「契約終了」が立っている取引先は使用不可として取り込む。
        $row['available'] = ! in_array('契約終了', (array) $this->raw($record, 'チェックボックス_0'), true);

        $row['information_security_answers'] = $this->answers($record, '情報管理に関する質問', self::INFO_SECURITY_MAP);
        $row['labor_contract_answers'] = $this->answers($record, '労働契約に関する質問', self::LABOR_CONTRACT_MAP);

        // freeeは未連携で取り込む（kintoneのIDは本番freeeのもので、この環境とは一致しない）。
        $row['freee_partner_id'] = null;
        $row['freee_synced_at'] = null;

        // 登録日時はkintoneのものを引き継ぐ。取り込み時刻を入れると全件が同じ日時になり、
        // 「新しい順」が意味を持たなくなる。
        $row['created_at'] = $this->timestamp($record, '作成日時') ?? now();
        $row['updated_at'] = $this->timestamp($record, '更新日時') ?? $row['created_at'];

        return $row;
    }

    /** 選択された選択肢を設問キーのJSONに変換する。未選択は空ではなくnullで持つ。 */
    private function answers(array $record, string $code, array $map): ?string
    {
        $selected = (array) $this->raw($record, $code);
        $answers = [];

        foreach ($selected as $label) {
            $key = $map[trim((string) $label)] ?? null;
            if ($key !== null) {
                $answers[$key] = true;
            }
        }

        if ($answers === []) {
            return null;
        }

        ksort($answers);

        return json_encode($answers, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 「東京都豊島区…」のように都道府県が先頭に入っているので切り出す。
     * 一致しなければ都道府県はnullのまま、住所はそのまま残す（欠落させない）。
     *
     * @return array{0: int|null, 1: string|null}
     */
    private function splitPrefecture(string $address): array
    {
        if ($address === '') {
            return [null, null];
        }

        foreach (self::PREFECTURES as $index => $prefecture) {
            if (str_starts_with($address, $prefecture)) {
                $rest = trim(mb_substr($address, mb_strlen($prefecture)));

                return [$index, $rest === '' ? null : $rest];
            }
        }

        return [null, $address];
    }

    /** 全角→半角に寄せ、空白と〒を落とす。 */
    private function halfWidth(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[\s\x{3000}]+/u', '', $value) ?? $value;
        if (class_exists(Normalizer::class)) {
            $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?: $value;
        }

        return str_replace(['〒', 'ー', '−', '–', '―'], ['', '-', '-', '-', '-'], $value);
    }

    private function normalizePostalCode(?string $value): ?string
    {
        $value = $this->halfWidth($value);

        return $value === '' ? null : $value;
    }

    /**
     * 法人番号は数字13桁。「7-2900-0200-4170」のような区切り付きを数字だけに戻す。
     * 13桁にならないものは入力誤りなので、そのまま持たずnullにして報告する
     * （不正な値を入れると、その取引先は画面から保存できなくなる）。
     */
    private function normalizeCorporateNumber(?string $value, string $name): ?string
    {
        $digits = preg_replace('/\D/', '', $this->halfWidth($value)) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) !== 13) {
            $this->invalid[] = "法人番号が13桁ではないため取り込みませんでした: {$name}（{$value}）";

            return null;
        }

        return $digits;
    }

    /**
     * 適格請求書番号は T + 数字13桁。実データには T が抜けた13桁だけのものが多いので補う
     * （登録番号は法人番号にTを付けたもの、という定義に沿う）。
     */
    private function normalizeInvoiceNumber(?string $value, string $name): ?string
    {
        $normalized = strtoupper($this->halfWidth($value));
        $normalized = preg_replace('/[^0-9T]/', '', $normalized) ?? '';

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^\d{13}$/', $normalized)) {
            return 'T'.$normalized;
        }

        if (! preg_match('/^T\d{13}$/', $normalized)) {
            $this->invalid[] = "適格請求書番号の形式が違うため取り込みませんでした: {$name}（{$value}）";

            return null;
        }

        return $normalized;
    }

    /** kintoneの日時（ISO8601・UTC）をアプリのタイムゾーンに直す。 */
    private function timestamp(array $record, string $code): ?string
    {
        $value = $this->trimmed($record, $code);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone'))->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function raw(array $record, string $code): mixed
    {
        return $record[$code]['value'] ?? null;
    }

    private function trimmed(array $record, string $code): string
    {
        return trim((string) ($this->raw($record, $code) ?? ''));
    }

    /** 全角半角・空白・大小の違いを畳んだ形。重複判定にだけ使う。 */
    private function normalizeName(string $value): string
    {
        if (class_exists(Normalizer::class)) {
            $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?: $value;
        }

        return mb_strtolower(preg_replace('/[\s\x{3000}]+/u', '', $value) ?? $value);
    }
}
