<?php

namespace App\Services\Freee;

use App\Models\FreeeCredential;
use App\Models\PartnerRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Normalizer;

/**
 * 取引先マスタ ⇄ freee会計の取引先の双方向同期。
 *
 * 双方向にするうえで避けられない問題は「どちらの編集が新しいか」を機械が決められないこと。
 * freeeが返す更新日時は `update_date`（日付のみ）で、同じ日の中の前後は分からない。
 * 時刻の比較で勝敗を決めると、同じ日に両方が触った瞬間に片方の編集が黙って消える。
 *
 * そこで **前回同期時点のfreeeの値（freee_snapshot）** を持ち、項目ごとに三方向で比べる：
 *   - こちらが変えた   = 現在のローカル値 ≠ スナップショット
 *   - freeeが変えた    = 現在のfreee値   ≠ スナップショット
 *   - 両方が変えていて値も食い違う → **競合。書かずに止めて人に決めさせる。**
 * 片方しか変えていない項目は、変えた方の値を採用してよい。
 *
 * 突き合わせのキーは freee_partner_id。未連携のときだけ名前の完全一致で拾う
 * （freeeの取引先APIに重複防止キーは無く、codeは既存データのほとんどが空のため使えない）。
 */
class PartnerFreeeSyncService
{
    public const RESULT_LINKED = 'linked';

    public const RESULT_CREATED = 'created';

    public const RESULT_UPDATED = 'updated';

    public const RESULT_UNCHANGED = 'unchanged';

    public const RESULT_PULLED = 'pulled';

    public function __construct(private readonly FreeeAccountingClient $accounting) {}

    /**
     * こちらの内容をfreeeへ反映する。未連携なら名前で探し、無ければ新規登録する。
     *
     * @param  bool  $force  競合を承知でこちらの値で上書きする
     * @return array{result: string, message: string, conflicts: array<int, array>}
     */
    public function push(FreeeCredential $credential, PartnerRecord $partner, bool $force = false): array
    {
        $name = trim((string) $partner->name);

        if ($name === '') {
            throw ValidationException::withMessages([
                'message' => '取引先名が空のため連携できません。',
            ]);
        }

        if (! $partner->isFreeeLinked()) {
            return $this->linkOrCreate($credential, $partner, $name);
        }

        $remote = $this->accounting->partner($credential, (int) $partner->freee_partner_id);

        if ($remote === null) {
            // IDが生きていないのに名前で探し直して別の取引先へ付け替えると、
            // 請求や入金の紐付けが黙って別の相手に移る。人が確かめるべき場面。
            throw ValidationException::withMessages([
                'message' => 'freeeに取引先ID '.$partner->freee_partner_id.' が見つかりません。'
                    .'freee側で削除された可能性があります。連携を解除してから登録し直してください。',
            ]);
        }

        $remoteValues = $this->fromFreee($remote);
        $conflicting = $this->conflictingFields($partner, $remoteValues);

        if ($conflicting !== [] && ! $force) {
            return [
                'result' => 'conflict',
                'message' => 'freee側でも変更されている項目があります。どちらの内容を残すか選んでください。',
                'conflicts' => $conflicting,
            ];
        }

        // 競合していない項目でfreee側だけが進んでいるものは、こちらへ取り込んでから送る。
        // これをしないと、全項目置換のPUTでfreee側の編集を巻き戻してしまう。
        $incoming = $force ? [] : $this->mergeFromRemote($partner, $remoteValues);
        if ($incoming !== []) {
            $partner->forceFill($incoming)->save();
        }

        $payload = $this->toFreee($partner);

        if (! $force && $this->sameAsRemote($payload, $remoteValues)) {
            $this->markSynced($partner, $remote);

            return [
                'result' => self::RESULT_UNCHANGED,
                'message' => 'freeeと同じ内容のため、更新は行いませんでした。',
                'conflicts' => [],
            ];
        }

        $saved = $this->accounting->updatePartner(
            $credential,
            (int) $partner->freee_partner_id,
            (string) $partner->name,
            $this->freeeAttributes($partner),
        );

        $this->markSynced($partner, $saved ?: $remote);

        return [
            'result' => self::RESULT_UPDATED,
            'message' => 'freeeの取引先を更新しました。',
            'conflicts' => [],
        ];
    }

    /**
     * freeeの内容でこちらを上書きする。競合したときの「freee側を採用する」選択肢。
     *
     * @return array{result: string, message: string, conflicts: array<int, array>}
     */
    public function pull(FreeeCredential $credential, PartnerRecord $partner): array
    {
        if (! $partner->isFreeeLinked()) {
            throw ValidationException::withMessages([
                'message' => 'この取引先はfreeeと連携していません。',
            ]);
        }

        $remote = $this->accounting->partner($credential, (int) $partner->freee_partner_id);

        if ($remote === null) {
            throw ValidationException::withMessages([
                'message' => 'freeeに取引先ID '.$partner->freee_partner_id.' が見つかりません。'
                    .'連携を解除してから登録し直してください。',
            ]);
        }

        $partner->forceFill($this->fromFreee($remote))->save();
        $this->markSynced($partner, $remote);

        return [
            'result' => self::RESULT_PULLED,
            'message' => 'freeeの内容を取り込みました。',
            'conflicts' => [],
        ];
    }

    /**
     * 連携先が実在するか、内容がずれていないかを確認する。書き込みは行わない。
     *
     * @return array{exists: bool, message: string, differences: array<int, array>}
     */
    public function check(FreeeCredential $credential, PartnerRecord $partner): array
    {
        if (! $partner->isFreeeLinked()) {
            throw ValidationException::withMessages([
                'message' => 'この取引先はfreeeと連携していません。',
            ]);
        }

        $remote = $this->accounting->partner($credential, (int) $partner->freee_partner_id);

        if ($remote === null) {
            return [
                'exists' => false,
                'message' => '取引先ID '.$partner->freee_partner_id.' はfreeeに存在しません。'
                    .'連携を解除して登録し直してください。',
                'differences' => [],
            ];
        }

        $remoteValues = $this->fromFreee($remote);
        $differences = [];

        foreach (PartnerRecord::FREEE_PULL_FIELDS as $field) {
            $local = $this->normalizeValue($partner->{$field});
            $freee = $this->normalizeValue($remoteValues[$field] ?? null);

            if ($local !== $freee) {
                $differences[] = [
                    'field' => $field,
                    'label' => self::FIELD_LABELS[$field] ?? $field,
                    'local' => $partner->{$field},
                    'freee' => $remoteValues[$field] ?? null,
                ];
            }
        }

        return [
            'exists' => true,
            'message' => $differences === []
                ? 'freeeと一致しています。'
                : 'freeeと異なる項目が'.count($differences).'件あります。',
            'differences' => $differences,
        ];
    }

    /**
     * 連携解除。freeeへのDELETEは行わず、こちらの紐付けだけを外す。
     */
    public function unlink(PartnerRecord $partner): void
    {
        $partner->forceFill([
            'freee_partner_id' => null,
            'freee_synced_at' => null,
            'freee_update_date' => null,
            'freee_snapshot' => null,
        ])->save();

        Log::info('freee partner link removed (freee side untouched).', [
            'partner_record_id' => $partner->id,
        ]);
    }

    /** 未連携の取引先を、名前でfreeeに紐付けるか新規登録する。 */
    private function linkOrCreate(FreeeCredential $credential, PartnerRecord $partner, string $name): array
    {
        // 作る／紐付けるの判断はキャッシュではなく今のfreeeを見る。
        // 古い一覧で判断すると、その間に作られた取引先を見落として重複を作る。
        $remotes = $this->accounting->cachedPartners($credential, fresh: true);

        $exact = array_values(array_filter($remotes, fn ($p) => (string) ($p['name'] ?? '') === $name));

        if (count($exact) > 1) {
            $ids = implode('、', array_map(fn ($p) => '#'.$p['id'], array_slice($exact, 0, 5)));
            throw ValidationException::withMessages([
                'message' => "freeeに「{$name}」という取引先が".count($exact)."件あります（{$ids}）。"
                    .'どれと紐付けるか決められないので、freee側を整理してから実行してください。',
            ]);
        }

        if (count($exact) === 1) {
            $this->link($partner, (int) $exact[0]['id']);
            // 紐付けただけの段階ではfreeeへ書かない。取り込むか送るかは人が選ぶ。
            $this->markSynced($partner, $exact[0]);

            return [
                'result' => self::RESULT_LINKED,
                'message' => 'freeeの既存の取引先と紐付けました（freeeへの書き込みは行っていません）。'
                    .'内容を反映する場合は「freeeへ反映」を実行してください。',
                'conflicts' => [],
            ];
        }

        // 全角半角・空白だけが違う候補があるなら作らない。
        // freeeには既にこの手の重複が存在するため、増やさない側に倒す。
        $twins = array_values(array_filter(
            $remotes,
            fn ($p) => $this->normalizeName((string) ($p['name'] ?? '')) === $this->normalizeName($name)
        ));

        if ($twins !== []) {
            $names = implode('、', array_map(fn ($p) => "「{$p['name']}」(#{$p['id']})", array_slice($twins, 0, 5)));
            throw ValidationException::withMessages([
                'message' => "freeeに表記が似た取引先があります: {$names}。"
                    .'同じ相手なら freee 側で名称を「'.$name.'」に統一してから、別の相手なら名称を変えてから実行してください。',
            ]);
        }

        $created = $this->accounting->createPartner($credential, $name, $this->freeeAttributes($partner));

        if (! filled($created['id'] ?? null)) {
            throw ValidationException::withMessages([
                'message' => 'freeeが取引先IDを返しませんでした。freee側の取引先一覧を確認してください。',
            ]);
        }

        $this->link($partner, (int) $created['id']);
        $this->markSynced($partner, $created);

        Log::info('freee partner created.', [
            'partner_record_id' => $partner->id,
            'freee_partner_id' => $created['id'],
            'name' => $name,
        ]);

        return [
            'result' => self::RESULT_CREATED,
            'message' => 'freeeに取引先を新規登録しました。',
            'conflicts' => [],
        ];
    }

    /** 紐付けを保存する。同じfreee取引先を2行に割り当てさせない。 */
    private function link(PartnerRecord $partner, int $freeePartnerId): void
    {
        DB::transaction(function () use ($partner, $freeePartnerId) {
            $taken = PartnerRecord::query()
                ->where('freee_partner_id', $freeePartnerId)
                ->whereKeyNot($partner->getKey())
                ->lockForUpdate()
                ->first();

            if ($taken) {
                throw ValidationException::withMessages([
                    'message' => 'この取引先は既に「'.$taken->name.'」と連携しています。',
                ]);
            }

            $partner->forceFill(['freee_partner_id' => $freeePartnerId])->save();
        });
    }

    /**
     * 両方が変更していて値も食い違う項目。空なら安全に同期できる。
     *
     * スナップショットが無い行（連携直後など）は比較の土台が無いので、
     * 値が違えばすべて競合として扱う——判断材料が無いまま上書きする方が危ない。
     *
     * @return array<int, array{field: string, label: string, local: mixed, freee: mixed}>
     */
    private function conflictingFields(PartnerRecord $partner, array $remoteValues): array
    {
        $snapshot = is_array($partner->freee_snapshot) ? $partner->freee_snapshot : null;
        $conflicts = [];

        foreach (PartnerRecord::FREEE_PULL_FIELDS as $field) {
            $local = $this->normalizeValue($partner->{$field});
            $freee = $this->normalizeValue($remoteValues[$field] ?? null);

            if ($local === $freee) {
                continue;
            }

            if ($snapshot === null) {
                $conflicts[] = $this->difference($field, $partner->{$field}, $remoteValues[$field] ?? null);

                continue;
            }

            $base = $this->normalizeValue($snapshot[$field] ?? null);
            $localChanged = $local !== $base;
            $remoteChanged = $freee !== $base;

            if ($localChanged && $remoteChanged) {
                $conflicts[] = $this->difference($field, $partner->{$field}, $remoteValues[$field] ?? null);
            }
        }

        return $conflicts;
    }

    /**
     * freee側だけが変わっている項目を取り出す。競合が無いことを確認してから呼ぶこと。
     *
     * @return array<string, mixed>
     */
    private function mergeFromRemote(PartnerRecord $partner, array $remoteValues): array
    {
        $snapshot = is_array($partner->freee_snapshot) ? $partner->freee_snapshot : null;
        $incoming = [];

        foreach (PartnerRecord::FREEE_PULL_FIELDS as $field) {
            $local = $this->normalizeValue($partner->{$field});
            $freee = $this->normalizeValue($remoteValues[$field] ?? null);

            if ($local === $freee) {
                continue;
            }

            // 土台が無いときは触らない。競合判定側で止まっているはず。
            if ($snapshot === null) {
                continue;
            }

            $base = $this->normalizeValue($snapshot[$field] ?? null);

            if ($local === $base) {
                $incoming[$field] = $remoteValues[$field] ?? null;
            }
        }

        return $incoming;
    }

    /** 同期済みとして記録する。スナップショットは必ずfreeeが返した値で取り直す。 */
    private function markSynced(PartnerRecord $partner, array $remote): void
    {
        $partner->forceFill([
            'freee_snapshot' => $this->fromFreee($remote),
            'freee_update_date' => $remote['update_date'] ?? null,
            'freee_synced_at' => now(),
        ])->save();
    }

    /** freeeの取引先レスポンスを、こちらの列名に写す。 */
    private function fromFreee(array $remote): array
    {
        $address = $remote['address_attributes'] ?? [];

        return [
            'name' => (string) ($remote['name'] ?? ''),
            'name_kana' => $remote['name_kana'] ?? null,
            'long_name' => $remote['long_name'] ?? null,
            'code' => $remote['code'] ?? null,
            'invoice_registration_number' => $remote['invoice_registration_number'] ?? null,
            'postal_code' => $address['zipcode'] ?? null,
            // freeeは未設定の都道府県を -1 で返す。有効値は 0〜46 なので null に寄せる
            // （こちらで -1 を保持すると「北海道の手前」のような無い値を持つことになる）。
            'prefecture_code' => $this->prefectureCode($address['prefecture_code'] ?? null),
            'address_1' => $address['street_name1'] ?? null,
            'address_2' => $address['street_name2'] ?? null,
            'phone' => $remote['phone'] ?? null,
            'contact_name' => $remote['contact_name'] ?? null,
            'email' => $remote['email'] ?? null,
            'available' => (bool) ($remote['available'] ?? true),
        ];
    }

    /** freeeの都道府県コード。0〜46が有効で、-1は未設定。 */
    private function prefectureCode(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $code = (int) $value;

        return ($code >= 0 && $code <= 46) ? $code : null;
    }

    /** 比較用に、こちらの値をfreeeの形（fromFreeeの戻り）へ揃える。 */
    private function toFreee(PartnerRecord $partner): array
    {
        $values = [];

        foreach (PartnerRecord::FREEE_PULL_FIELDS as $field) {
            $values[$field] = $partner->{$field};
        }

        return $values;
    }

    /** freeeへ送るボディ（name と company_id はクライアント側で付ける）。 */
    private function freeeAttributes(PartnerRecord $partner): array
    {
        $address = array_filter([
            'zipcode' => $partner->postal_code,
            'prefecture_code' => $partner->prefecture_code,
            'street_name1' => $partner->address_1,
            'street_name2' => $partner->address_2,
        ], fn ($value) => $value !== null && $value !== '');

        $attributes = [
            'code' => $partner->code,
            'name_kana' => $partner->name_kana,
            'long_name' => $partner->long_name,
            'phone' => $partner->phone,
            'contact_name' => $partner->contact_name,
            'email' => $partner->email,
            'invoice_registration_number' => $partner->invoice_registration_number,
        ];

        if ($address !== []) {
            $attributes['address_attributes'] = $address;
        }

        return $attributes;
    }

    /** 送る内容がfreeeの現在値と同じか（無駄なPUTを避ける）。 */
    private function sameAsRemote(array $local, array $remoteValues): bool
    {
        foreach (PartnerRecord::FREEE_PUSH_FIELDS as $field) {
            if ($this->normalizeValue($local[$field] ?? null) !== $this->normalizeValue($remoteValues[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function difference(string $field, mixed $local, mixed $freee): array
    {
        return [
            'field' => $field,
            'label' => self::FIELD_LABELS[$field] ?? $field,
            'local' => $local,
            'freee' => $freee,
        ];
    }

    /** null と空文字はfreee側で区別されないため、比較では同じものとして扱う。 */
    private function normalizeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }

    /** 全角半角・空白・大小の違いを畳んだ形。同一視ではなく「紛らわしい」の判定に使う。 */
    private function normalizeName(string $value): string
    {
        if (class_exists(Normalizer::class)) {
            $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?: $value;
        }

        return mb_strtolower(preg_replace('/[\s\x{3000}]+/u', '', $value) ?? $value);
    }

    /** 競合・差分の表示に使う日本語名。 */
    private const FIELD_LABELS = [
        'name' => '取引先名',
        'name_kana' => 'カナ',
        'long_name' => '正式名称',
        'code' => '取引先コード',
        'invoice_registration_number' => '登録番号',
        'postal_code' => '郵便番号',
        'prefecture_code' => '都道府県',
        'address_1' => '住所1',
        'address_2' => '住所2',
        'phone' => '電話番号',
        'contact_name' => '担当者',
        'email' => 'メール',
        'available' => '状態',
    ];
}
