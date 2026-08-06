<?php

namespace App\Support;

use App\Models\FlowRecord;
use App\Models\User;
use App\Services\Freee\FreeePartnerSyncService;
use App\Services\Freee\FreeeReauthorizationRequiredException;
use Illuminate\Validation\ValidationException;

/**
 * カスタムボタンから呼ばれる処理の置き場。
 *
 * 1処理 = このクラスのメソッド1つ + ACTIONS に1行。ボタンの設定に入るのはそのメソッド名だけで、
 * 中身は全部ここ＝コードに書く。フィールドの読み方も書き戻し方も宣言せず、必要なことをそのまま
 * PHPで書けばよい（kintoneのカスタマイズJSをサーバー側でやる、という位置づけ）。
 *
 * URLやクラス名を設定に持たせないのは意図的。設定に書かれた宛先をサーバーが呼ぶ形にすると、
 * アプリ管理者が任意の内部エンドポイントへレコードを送れてしまう。ACTIONS に載っている名前
 * だけが呼ばれ、それ以外は実行されない。
 *
 * 追加のしかた：
 *   1. ACTIONS に 'メソッド名' => '画面に出す名前' を足す
 *   2. 同じ名前の public メソッドを書く
 *
 * メソッドの約束：
 *   - 引数は (FlowRecord $record, User $user)
 *   - 戻り値は ['message' => '完了時に出す文', 'values' => ['フィールドコード' => 値]]（どちらも任意）
 *     values に入れたものは FlowRecordActionService が書き戻して変更履歴に残す。
 *     編集させたくないフィールド（編集権限なし）にも入る——保存エンドポイントを通らないため。
 *   - 実行すべきでない状態なら ValidationException を投げる（画面にそのメッセージが出る）。
 */
class FlowRecordActions
{
    /**
     * 実行を許可するメソッド名 => 設定画面に出す名前。
     *
     * @var array<string, string>
     */
    public const ACTIONS = [
        'syncFreeePartner' => 'freee連携（取引先の確認・登録）',
    ];

    /** 取引先アプリの項目コード。ボタンの設定ではなくここで固定する。 */
    private const PARTNER_NAME_KEY = '会社名';

    private const PARTNER_ID_KEY = '取引先ID';

    private const SYNCED_AT_KEY = 'freee同期確認日時';

    public function __construct(private readonly FreeePartnerSyncService $partners) {}

    /** 設定画面用の一覧。 */
    public static function catalog(): array
    {
        $out = [];
        foreach (self::ACTIONS as $method => $label) {
            $out[] = ['key' => $method, 'label' => $label];
        }

        return $out;
    }

    /** 許可リストに載っていて、実体のあるメソッドかどうか。 */
    public static function isCallable(?string $method): bool
    {
        return filled($method)
            && array_key_exists($method, self::ACTIONS)
            && method_exists(self::class, $method);
    }

    /**
     * 実行。呼べることは呼び出し側（FlowRecordActionService）が確認済み。
     *
     * @return array{message?: string, values?: array<string, mixed>}
     */
    public function run(string $method, FlowRecord $record, User $user): array
    {
        $result = $this->{$method}($record, $user);

        return is_array($result) ? $result : [];
    }

    /* ================================================================
     | ここから下が実際の処理。1メソッド1ボタン。
     |================================================================ */

    /**
     * 取引先アプリ ⇄ freee会計の取引先。
     *
     * 取引先IDが入っていれば実在を確かめるだけ、空なら名前で探して、無ければ作る。
     * どの経路でも最後に確認日時を打つので、「いつ突き合わせたか」がレコードに残る。
     */
    public function syncFreeePartner(FlowRecord $record, User $user): array
    {
        try {
            $result = $this->partners->sync(
                FreeePartnerSyncService::credential(),
                $record,
                self::PARTNER_NAME_KEY,
                self::PARTNER_ID_KEY,
            );
        } catch (FreeeReauthorizationRequiredException $e) {
            // これは RuntimeException なので、そのままだと500になる。画面に出す文に変える。
            throw ValidationException::withMessages([
                'message' => 'freeeとの接続が切れています。管理画面 > 施設 > freee で再認可してください。',
            ]);
        }

        $name = $result['partner_name'];
        $id = $result['partner_id'];

        $message = match ($result['result']) {
            FreeePartnerSyncService::RESULT_VERIFIED => "freeeの取引先「{$name}」(ID {$id}) と一致しました。",
            FreeePartnerSyncService::RESULT_LINKED => "freeeの既存の取引先「{$name}」(ID {$id}) と紐付けました。",
            default => "freeeに取引先「{$name}」(ID {$id}) を作成しました。",
        };

        // 確認日時は毎回更新する。取引先IDは、新しく決まったときだけ書く。
        $values = [self::SYNCED_AT_KEY => now()->format('Y-m-d\\TH:i')];
        if ($result['result'] !== FreeePartnerSyncService::RESULT_VERIFIED) {
            $values[self::PARTNER_ID_KEY] = $id;
        }

        return ['message' => $message, 'values' => $values];
    }
}
