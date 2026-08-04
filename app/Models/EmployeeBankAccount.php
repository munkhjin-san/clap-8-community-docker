<?php

namespace App\Models;

use App\Services\AccountVault;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 従業員の振込口座。管理者のみが登録・閲覧する。
 *
 * 口座番号だけが暗号化対象。金融機関名・支店名・名義が揃っても番号が無ければ振込には使えないので、
 * 番号を伏せれば残りは単体では役に立たない。名義は平文：ほぼ本人名であり users.name に平文で
 * 入っている以上ここだけ暗号化しても守れるものがない（EmployeeContractPrivateDetail と同じ判断）。
 * ただし旧姓や家族名義の口座を扱うようになったら暗号化を検討すること。
 *
 * 暗号化には Laravel の 'encrypted' キャストではなく AccountVault を使う。フロー側の
 * パスワードフィールドが AccountVault なので、同じ番号が2箇所に暗号化されて存在する際に
 * 鍵とローテーション手順を1つに揃えられる（'encrypted' は APP_KEY で別系統になる）。
 */
class EmployeeBankAccount extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 誤ってJSONに載せないための既定の目隠し。**これはセキュリティ境界ではない**：
     * toArray()/toJson() にしか効かず、->value() や ->pluck()、生クエリ、makeVisible() には
     * 何の効果もない。実際の制御は「管理者のみが通る1本の経路」と閲覧ログ側で行う。
     */
    protected $hidden = ['account_number'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->select('id', 'name');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id')->select('id', 'name');
    }

    /**
     * 番号。読むと復号、書くと暗号化。
     *
     * set が配列を返すことで下4桁を同じ書き込みの中で更新する — 呼び出し側が忘れる余地がなく、
     * 表示用の下4桁が本体とズレることが原理的に起きない。
     *
     * 復号は失敗をそのまま投げる（DecryptException）。鍵の不一致を「値なし」に丸めると、
     * 鍵を壊した／ローテーションを間違えた状態が「空の口座」と見分けられなくなる。
     */
    protected function accountNumber(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ($value === null || $value === '')
                ? null
                : app(AccountVault::class)->decrypt($value),
            set: function ($value) {
                $plain = $value === null ? '' : trim((string) $value);
                if ($plain === '') {
                    return ['account_number' => null, 'account_number_last4' => null];
                }

                return [
                    'account_number' => app(AccountVault::class)->encrypt($plain),
                    'account_number_last4' => mb_substr($plain, -4),
                ];
            },
        );
    }

    /**
     * 一覧・フォーム用の伏せ字。復号しない（下4桁は平文列）。
     *
     * 4桁以下の番号は下4桁＝全桁になるので、伏せ字にした結果が元の値そのものになってしまう。
     * その場合は下4桁を出さない。桁数の足りない番号は本来データ不備（取り込み時に警告が出る）だが、
     * 「伏せているつもりで全部見えている」状態を作らないことのほうが大事。
     */
    public function maskedNumber(): ?string
    {
        $last4 = $this->account_number_last4;
        if ($last4 === null) {
            return null;
        }

        return strlen($last4) < 4 ? '****' : '****'.$last4;
    }
}
