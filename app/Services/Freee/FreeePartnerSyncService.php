<?php

namespace App\Services\Freee;

use App\Models\FlowRecord;
use App\Models\FreeeCredential;
use App\Services\FlowService;
use Illuminate\Validation\ValidationException;
use Normalizer;

/**
 * カスタムアプリの「取引先」レコードを、freee会計の取引先と突き合わせる。
 *
 * やることは3通りしかない：
 *   1. 取引先IDが入っている → freeeに実在するか確かめるだけ（確認日時を更新）
 *   2. 空 → 名前が一致する取引先を探して、あればそのIDを入れる
 *   3. 空で、名前でも見つからない → freeeに作ってIDを入れる
 *
 * **IDが入っているのにfreeeで見つからない場合は止める。** 名前で探し直して別の取引先に
 * 付け替えると、請求や入金の紐付けが黙って別の相手に移る。人が確かめるべき場面。
 *
 * 突き合わせは名前の完全一致だけを「同じもの」とみなす。空白や大文字小文字だけが違う
 * 候補があるときも作らずに止める——似た名前の取引先が2つ並ぶと、後でどちらが正か分からなくなる。
 */
class FreeePartnerSyncService
{
    /** 何が起きたか。呼び出し側がメッセージを組み立てるのに使う。 */
    public const RESULT_VERIFIED = 'verified';

    public const RESULT_LINKED = 'linked';

    public const RESULT_CREATED = 'created';

    public function __construct(
        private readonly FreeeAccountingClient $accounting,
        private readonly FlowService $flow,
    ) {}

    /**
     * @return array{result: string, partner_id: int, partner_name: string}
     */
    public function sync(FreeeCredential $credential, FlowRecord $record, string $nameKey, string $partnerIdKey): array
    {
        $name = trim((string) $this->value($record, $nameKey));
        $partnerId = (int) $this->value($record, $partnerIdKey);

        if ($partnerId > 0) {
            return $this->verify($credential, $partnerId);
        }

        if ($name === '') {
            throw ValidationException::withMessages([
                'message' => '取引先IDも会社名も空です。会社名を入れてから実行してください。',
            ]);
        }

        return $this->linkOrCreate($credential, $name);
    }

    /** 既に入っているIDが本物か。違えば止める。 */
    private function verify(FreeeCredential $credential, int $partnerId): array
    {
        $partner = $this->accounting->partner($credential, $partnerId);

        if ($partner === null) {
            throw ValidationException::withMessages([
                'message' => "freeeに取引先ID {$partnerId} が見つかりません。IDが正しいか、"
                    .'別の事業所のIDでないかを確認してください（自動では付け替えません）。',
            ]);
        }

        return [
            'result' => self::RESULT_VERIFIED,
            'partner_id' => (int) ($partner['id'] ?? $partnerId),
            'partner_name' => (string) ($partner['name'] ?? ''),
        ];
    }

    /** 名前で探して、あれば紐付け、無ければ作る。 */
    private function linkOrCreate(FreeeCredential $credential, string $name): array
    {
        // 作る／紐付ける判断なので、キャッシュではなく今のfreeeを見る
        $partners = $this->accounting->cachedPartners($credential, fresh: true);

        $exact = array_values(array_filter($partners, fn ($p) => (string) ($p['name'] ?? '') === $name));

        if (count($exact) === 1) {
            return [
                'result' => self::RESULT_LINKED,
                'partner_id' => (int) $exact[0]['id'],
                'partner_name' => (string) $exact[0]['name'],
            ];
        }

        if (count($exact) > 1) {
            $ids = implode('、', array_map(fn ($p) => '#'.$p['id'], array_slice($exact, 0, 5)));
            throw ValidationException::withMessages([
                'message' => "freeeに「{$name}」という取引先が".count($exact)."件あります（{$ids}）。"
                    .'どれと紐付けるかを決められないので、freee側を整理するか、取引先IDを直接入れてください。',
            ]);
        }

        // 完全一致は無いが、空白や大小の違いだけの候補があるなら作らない
        $near = array_values(array_filter(
            $partners,
            fn ($p) => $this->normalize((string) ($p['name'] ?? '')) === $this->normalize($name)
        ));
        if ($near !== []) {
            $names = implode('、', array_map(fn ($p) => "「{$p['name']}」(#{$p['id']})", array_slice($near, 0, 5)));
            throw ValidationException::withMessages([
                'message' => "freeeに似た名前の取引先があります: {$names}。"
                    .'同じ相手であれば取引先IDを直接入れてください。別の相手なら名前を変えてから実行してください。',
            ]);
        }

        $created = $this->accounting->createPartner($credential, $name);

        return [
            'result' => self::RESULT_CREATED,
            'partner_id' => (int) ($created['id'] ?? 0),
            'partner_name' => (string) ($created['name'] ?? $name),
        ];
    }

    /** レコードの値を項目コードで読む（recordValues は項目IDのキーで返るため）。 */
    private function value(FlowRecord $record, string $key): mixed
    {
        $definition = $record->definition;
        $field = $definition->fields->firstWhere('key', $key);
        if (! $field) {
            throw ValidationException::withMessages([
                'message' => "このアプリに「{$key}」という項目がありません。ボタンの設定を確認してください。",
            ]);
        }

        return $this->flow->recordValues($record, $definition->fields)[(string) $field->id] ?? null;
    }

    /** 全角半角・大小・空白の違いを無視した形。同一視ではなく「紛らわしい」の判定に使う。 */
    private function normalize(string $name): string
    {
        $n = class_exists(Normalizer::class) ? (Normalizer::normalize($name, Normalizer::FORM_KC) ?: $name) : $name;

        return mb_strtolower(preg_replace('/[\s\x{3000}]+/u', '', $n) ?? $n);
    }

    /**
     * 使うfreee設定。接続済みで有効なものの先頭（company_id にユニーク制約があるため実質1事業所）。
     */
    public static function credential(): FreeeCredential
    {
        $credential = FreeeCredential::query()
            ->where('active', true)
            ->where('status', FreeeCredential::STATUS_CONNECTED)
            ->orderBy('id')
            ->first();

        if (! $credential) {
            throw ValidationException::withMessages([
                'message' => '連携済みのfreee設定がありません。管理画面 > 施設 > freee で認可してください。',
            ]);
        }

        return $credential;
    }
}
