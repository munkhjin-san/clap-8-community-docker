<?php

namespace App\Http\Controllers;

use App\Models\FreeeCredential;
use App\Models\PartnerRecord;
use App\Models\ProjectRecord;
use App\Models\User;
use App\Services\Freee\FreeeReauthorizationRequiredException;
use App\Services\Freee\PartnerFreeeSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * 取引先マスタの管理（管理画面 > プロジェクト管理 > 取引先）。
 *
 * freeeとの同期そのものは PartnerFreeeSyncService が持つ。ここは入口と権限だけ。
 */
class PartnerRecordController extends Controller
{
    /** 一覧の並び替えに使える列。 */
    private const SORTABLE = ['name', 'name_kana', 'code', 'updated_at', 'created_at', 'freee_partner_id'];

    /** 空文字比較ができない（文字列ではない）並び替え列。 */
    private const NON_TEXT_SORTS = ['created_at', 'updated_at', 'freee_partner_id'];

    public function __construct(private readonly PartnerFreeeSyncService $sync) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sort' => ['nullable', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            // 連携状態での絞り込み。未指定なら全件。
            'linked' => ['nullable', Rule::in(['linked', 'unlinked'])],
            'available' => ['nullable', Rule::in(['available', 'unavailable'])],
            'entity_type' => ['nullable', 'array'],
            'entity_type.*' => [Rule::in(PartnerRecord::ENTITY_TYPES)],
            'transaction_category' => ['nullable', 'array'],
            'transaction_category.*' => [Rule::in(PartnerRecord::TRANSACTION_CATEGORIES)],
        ]);

        // 既定は登録が新しい順。kintoneの作成日時を引き継いでいるので、実際の登録順になる。
        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';

        $partners = PartnerRecord::query()
            ->with(['projects:id,name', 'creator'])
            ->search($validated['keyword'] ?? null)
            ->when(
                ($validated['linked'] ?? null) === 'linked',
                fn ($q) => $q->whereNotNull('freee_partner_id'),
            )
            ->when(
                ($validated['linked'] ?? null) === 'unlinked',
                fn ($q) => $q->whereNull('freee_partner_id'),
            )
            ->when(
                ($validated['available'] ?? null) !== null,
                fn ($q) => $q->where('available', $validated['available'] === 'available'),
            )
            // 複数選択はOR（選んだ区分のいずれか）。未選択は絞り込まない。
            ->when(
                ! empty($validated['entity_type']),
                fn ($q) => $q->whereIn('entity_type', $validated['entity_type']),
            )
            ->when(
                ! empty($validated['transaction_category']),
                fn ($q) => $q->whereIn('transaction_category', $validated['transaction_category']),
            )
            // 空欄が多いデータなので、空は方向に関わらず最後へ回す。
            // 文字列列だけ空文字も見る。日時・数値列に `= ''` を当てるとMySQLが
            // 「Incorrect TIMESTAMP value」で落ちるため、NULL判定のみにする。
            ->orderByRaw(
                in_array($sort, self::NON_TEXT_SORTS, true)
                    ? "CASE WHEN {$sort} IS NULL THEN 1 ELSE 0 END"
                    : "CASE WHEN {$sort} IS NULL OR {$sort} = '' THEN 1 ELSE 0 END"
            )
            ->orderBy($sort, $direction)
            ->orderBy('id', 'asc')
            ->paginate($validated['per_page'] ?? 50, ['*'], 'page', $validated['page'] ?? 1);

        return response()->json([
            'partners' => $partners->items(),
            'meta' => [
                'page' => $partners->currentPage(),
                'per_page' => $partners->perPage(),
                'total_count' => $partners->total(),
                'last_page' => $partners->lastPage(),
                'has_more' => $partners->hasMorePages(),
                'from' => $partners->firstItem() ?? 0,
                'to' => $partners->lastItem() ?? 0,
                'sort' => $sort,
                'direction' => $direction,
                'sortable' => self::SORTABLE,
            ],
        ]);
    }

    /**
     * 登録はこちらのDBのみ。freeeへの反映は「freee連携」の操作（push/pull）で明示的に行う。
     * 保存のたびにfreeeへ書きに行くと、意図しないタイミングで相手側を書き換えてしまう。
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validatedPartner($request);

        $partner = PartnerRecord::query()->create($validated + [
            'created_by' => $this->activeUserId(),
        ]);

        return response()->json([
            'partner' => $this->payload($partner->fresh()),
            'message' => '取引先を登録しました。',
        ], 201);
    }

    public function update(Request $request, PartnerRecord $partner): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validatedPartner($request, $partner);

        $partner->update($validated);

        return response()->json([
            'partner' => $this->payload($partner->fresh()),
            'message' => '取引先を更新しました。',
        ]);
    }

    /**
     * 削除。freee側は消さない（他システムの仕訳から参照されているため）。
     */
    public function destroy(PartnerRecord $partner): JsonResponse
    {
        $this->authorizeAdmin();

        $partner->projects()->detach();
        $partner->delete();

        return response()->json(['message' => '取引先を削除しました（freee側は変更していません）。']);
    }

    /** こちらの内容をfreeeへ反映する（未連携なら紐付けか新規登録）。 */
    public function pushToFreee(Request $request, PartnerRecord $partner): JsonResponse
    {
        $this->authorizeAdmin();

        $result = $this->guardFreee(
            fn () => $this->sync->push($this->connectedCredential(), $partner, $request->boolean('force')),
        );

        return response()->json($result + ['partner' => $this->payload($partner->fresh())]);
    }

    /** freeeの内容でこちらを上書きする。 */
    public function pullFromFreee(PartnerRecord $partner): JsonResponse
    {
        $this->authorizeAdmin();

        $result = $this->guardFreee(fn () => $this->sync->pull($this->connectedCredential(), $partner));

        return response()->json($result + ['partner' => $this->payload($partner->fresh())]);
    }

    /** 連携先が生きているか・差分があるかの確認。書き込みは行わない。 */
    public function checkFreee(PartnerRecord $partner): JsonResponse
    {
        $this->authorizeAdmin();

        $result = $this->guardFreee(fn () => $this->sync->check($this->connectedCredential(), $partner));

        return response()->json($result + ['partner' => $this->payload($partner->fresh())]);
    }

    /** 連携解除。freeeへのDELETEは行わない。 */
    public function unlinkFreee(PartnerRecord $partner): JsonResponse
    {
        $this->authorizeAdmin();

        $this->sync->unlink($partner);

        return response()->json([
            'message' => '連携を解除しました（freee側は変更していません）。',
            'partner' => $this->payload($partner->fresh()),
        ]);
    }

    /**
     * この取引先に紐付くプロジェクトを置き換える。
     */
    public function syncProjects(Request $request, PartnerRecord $partner): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'project_ids' => ['present', 'array'],
            'project_ids.*' => ['integer', 'exists:project_records,id'],
        ]);

        $partner->projects()->sync($validated['project_ids']);

        return response()->json([
            'message' => '紐付けを保存しました。',
            'partner' => $this->payload($partner->fresh()),
        ]);
    }

    /** 紐付け先を選ぶためのプロジェクト一覧（軽量）。 */
    public function selectableProjects(): JsonResponse
    {
        $this->authorizeAdmin();

        return response()->json([
            'projects' => ProjectRecord::query()
                ->select('id', 'name', 'status', 'date_start', 'date_end')
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function validatedPartner(Request $request, ?PartnerRecord $partner = null): array
    {
        // 想定外のキーが混ざったまま保存されると、後から設問との対応が取れなくなる。
        $this->rejectUnknownAnswerKeys($request, 'information_security_answers', PartnerRecord::INFO_SECURITY_KEY_PATTERN);
        $this->rejectUnknownAnswerKeys($request, 'labor_contract_answers', PartnerRecord::LABOR_CONTRACT_KEY_PATTERN);

        return $request->validate([
            // 名前がfreeeとの突き合わせキーなので、こちら側では重複を許さない。
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('partner_records', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($partner?->id),
            ],
            'name_kana' => ['nullable', 'string', 'max:255'],
            'long_name' => ['nullable', 'string', 'max:255'],
            'entity_type' => ['nullable', Rule::in(PartnerRecord::ENTITY_TYPES)],
            'transaction_category' => ['nullable', Rule::in(PartnerRecord::TRANSACTION_CATEGORIES)],
            'code' => ['nullable', 'string', 'max:255'],
            'corporate_number' => ['nullable', 'string', 'size:13', 'regex:/^\d{13}$/'],
            'invoice_registration_number' => ['nullable', 'string', 'max:14', 'regex:/^T\d{13}$/'],
            'postal_code' => ['nullable', 'string', 'max:16'],
            'prefecture_code' => ['nullable', 'integer', 'min:0', 'max:46'],
            'address_1' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_position' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
            'available' => ['nullable', 'boolean'],
            'isms_registration_number' => ['nullable', 'string', 'max:255'],
            'privacy_mark_number' => ['nullable', 'string', 'max:255'],
            // 回答は「設問キー => 真偽値」。設問文はサーバーで持たないので、
            // キーの形と値の型だけを検証する（設問の増減で壊れないようにするため）。
            'information_security_answers' => ['nullable', 'array'],
            'information_security_answers.*' => ['boolean'],
            'labor_contract_answers' => ['nullable', 'array'],
            'labor_contract_answers.*' => ['boolean'],
        ], [
            'name.unique' => 'この取引先名は既に登録されています。',
            'corporate_number.regex' => '法人番号は13桁の数字で入力してください。',
            'invoice_registration_number.regex' => '登録番号は T + 13桁の数字で入力してください。',
        ]);
    }

    /**
     * ヒアリング回答のキーが決められた形（is_01 / lc_01…）であることを確かめる。
     */
    private function rejectUnknownAnswerKeys(Request $request, string $field, string $pattern): void
    {
        $answers = $request->input($field);

        if (! is_array($answers)) {
            return;
        }

        $unknown = array_values(array_filter(
            array_keys($answers),
            fn ($key) => ! is_string($key) || preg_match($pattern, $key) !== 1,
        ));

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                $field => '設問キーが不正です（'.implode('、', array_slice($unknown, 0, 5)).'）。',
            ]);
        }
    }

    private function payload(PartnerRecord $partner): PartnerRecord
    {
        return $partner->load(['projects:id,name', 'creator']);
    }

    /**
     * 再認可が必要な状態を、画面が読めるメッセージへ変える。
     */
    private function guardFreee(callable $callback): array
    {
        try {
            return $callback();
        } catch (FreeeReauthorizationRequiredException $exception) {
            throw ValidationException::withMessages(['message' => $exception->getMessage()]);
        }
    }

    private function connectedCredential(): FreeeCredential
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

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        abort_unless($user, 401, '認証が必要です。');

        abort_unless(in_array($this->activeUserId(), User::ADMIN_USER_IDS, true), 403, '管理者権限がありません。');
    }

    /**
     * 実際に操作しているユーザー。代理ログイン中は切り替え先を見る
     * （Auth::user() のままだと本人のIDで権限判定してしまう）。
     */
    private function activeUserId(): int
    {
        $user = Auth::user();

        $sub = $user->linked()
            ->where('main_id', Auth::id())
            ->wherePivot('active', 1)
            ->first();

        return (int) ($sub?->id ?? $user->id);
    }
}
