<?php

namespace App\Support\FlowActions;

use App\Models\FlowRecord;
use App\Models\FreeeCredential;
use App\Models\User;
use App\Services\Freee\FreeeAccountingClient;
use App\Services\Freee\FreeeReauthorizationRequiredException;
use Illuminate\Validation\ValidationException;

/**
 * レコードの内容でfreee会計に取引先を新規作成し、返ってきた取引先IDを書き戻す。
 *
 * 取り消しはこのボタンからはできないので、freee_partner_id が入っているレコードは
 * doneFieldKey() により「実行済み」になり二重登録できない。
 */
class FreeePartnerCreateAction extends FlowRecordAction
{
    public function __construct(private readonly FreeeAccountingClient $accounting) {}

    public static function key(): string
    {
        return 'freee_partner_create';
    }

    public static function label(): string
    {
        return 'freeeに取引先を登録';
    }

    public static function description(): string
    {
        return 'このレコードの内容でfreee会計に取引先を新規作成し、freeeが発行した取引先IDを書き戻します。';
    }

    public static function inputs(): array
    {
        return [
            'partner_name' => ['label' => '取引先名', 'required' => true],
            'partner_name_kana' => ['label' => '取引先名（カナ）'],
            'partner_code' => ['label' => '取引先コード'],
            'partner_long_name' => ['label' => '正式名称'],
        ];
    }

    public static function outputs(): array
    {
        return [
            'freee_partner_id' => ['label' => 'freee取引先ID'],
        ];
    }

    public static function doneFieldKey(): ?string
    {
        return 'freee_partner_id';
    }

    public static function confirmMessage(): string
    {
        return 'freee会計に取引先を新規登録します。登録後はこのボタンから取り消せません。実行しますか？';
    }

    public function run(FlowRecord $record, User $user, array $input): array
    {
        $credential = FreeeCredential::query()
            ->where('active', true)
            ->where('status', FreeeCredential::STATUS_CONNECTED)
            ->orderBy('id')
            ->first();

        if (! $credential) {
            throw ValidationException::withMessages([
                'message' => '連携済みのfreee設定がありません。管理画面のfreeeタブで認可してください。',
            ]);
        }

        $body = array_filter([
            'company_id' => $this->companyId($credential),
            'name' => $this->text($input['partner_name'] ?? null),
            'name_kana' => $this->text($input['partner_name_kana'] ?? null),
            'code' => $this->text($input['partner_code'] ?? null),
            'long_name' => $this->text($input['partner_long_name'] ?? null),
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $payload = $this->accounting->post($credential, '/api/1/partners', $body);
        } catch (FreeeReauthorizationRequiredException $exception) {
            throw ValidationException::withMessages(['message' => $exception->getMessage()]);
        }

        $partner = $payload['partner'] ?? [];
        $partnerId = $partner['id'] ?? null;

        if (! $partnerId) {
            // IDが返らないのに成功扱いすると、書き戻しが空のまま「実行済み」にならず二重登録を招く。
            throw ValidationException::withMessages([
                'message' => 'freeeから取引先IDが返りませんでした。freee側の登録状況を確認してください。',
            ]);
        }

        // 一覧キャッシュ（10分）は作成直後から古い。
        $this->accounting->forgetPartners($credential);

        return [
            'message' => 'freeeに取引先を登録しました（取引先ID: '.$partnerId.'）。',
            'values' => ['freee_partner_id' => (string) $partnerId],
        ];
    }

    /**
     * 呼び出しに使う事業所ID。FreeeBaseClient と同じ優先順（設定 → 認可時の値）。
     */
    private function companyId(FreeeCredential $credential): int
    {
        $configured = config('services.freee.company_id');
        $companyId = (int) (filled($configured) ? $configured : $credential->company_id);

        if ($companyId <= 0) {
            throw ValidationException::withMessages([
                'message' => 'freeeの事業所IDが未設定です。認可をやり直すか、FREEE_COMPANY_IDを設定してください。',
            ]);
        }

        return $companyId;
    }

    private function text(mixed $value): ?string
    {
        return is_scalar($value) ? trim((string) $value) : null;
    }
}
