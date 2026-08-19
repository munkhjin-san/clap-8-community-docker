<?php

namespace App\Http\Controllers;

use App\Models\FreeeCredential;
use App\Models\ProjectRecord;
use App\Models\User;
use App\Services\Freee\FreeeAccountingClient;
use App\Services\Freee\FreeeApiClient;
use App\Services\Freee\FreeeReauthorizationRequiredException;
use App\Services\Freee\FreeeSectionSyncService;
use App\Services\Freee\FreeeTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminFreeeController extends Controller
{
    /** 管理画面のfreeeタブ。認可完了後にここへ戻す。 */
    private const ADMIN_TAB_PATH = '/admin_control/facilities/freee';

    private const SESSION_STATE = 'freee_oauth_state';

    private const SESSION_CREDENTIAL = 'freee_oauth_credential_id';

    public function __construct(
        private readonly FreeeTokenService $tokens,
        private readonly FreeeApiClient $api,
        private readonly FreeeAccountingClient $accounting,
        private readonly FreeeSectionSyncService $sections,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorizeAdmin();

        $credentials = FreeeCredential::query()
            ->with('authorizer:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn (FreeeCredential $credential) => $credential->adminPayload());

        return response()->json([
            'credentials' => $credentials,
            // freeeアプリ管理側に登録すべきコールバックURL。完全一致が必要。
            'callback_url' => url('/admin/freee/callback'),
            'oob_redirect_uri' => FreeeCredential::OOB_REDIRECT_URI,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validatedCredential($request);
        $credential = FreeeCredential::query()->create($validated + [
            'status' => FreeeCredential::STATUS_UNCONFIGURED,
        ]);

        return response()->json($credential->adminPayload(), 201);
    }

    public function update(Request $request, FreeeCredential $freeeCredential): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validatedCredential($request, $freeeCredential);

        // client_id を差し替えたら既存トークンは別アプリのものになるため破棄する。
        $clientChanged = array_key_exists('client_id', $validated)
            && $validated['client_id'] !== $freeeCredential->client_id;

        $freeeCredential->update($validated);

        if ($clientChanged && $freeeCredential->isConnected()) {
            $this->tokens->disconnect($freeeCredential);
        }

        return response()->json($freeeCredential->fresh()->adminPayload());
    }

    public function destroy(FreeeCredential $freeeCredential): JsonResponse
    {
        $this->authorizeAdmin();

        $freeeCredential->delete();

        return response()->json(['message' => 'freee連携設定を削除しました。']);
    }

    /**
     * 認可フローの開始。stateをセッションに保存し、freeeの同意画面URLを返す。
     * 画面側はこのURLへ遷移させる。
     */
    public function connect(Request $request, FreeeCredential $freeeCredential): JsonResponse
    {
        $this->authorizeAdmin();

        $state = Str::random(40);

        $request->session()->put(self::SESSION_STATE, $state);
        $request->session()->put(self::SESSION_CREDENTIAL, $freeeCredential->id);

        $url = $this->tokens->authorizationUrl($freeeCredential, $state);

        $freeeCredential->forceFill([
            'status' => $freeeCredential->isConnected()
                ? $freeeCredential->status
                : FreeeCredential::STATUS_AWAITING_CONSENT,
        ])->save();

        return response()->json([
            'authorization_url' => $url,
            // OOBの場合はリダイレクトが返ってこないので、画面側でコード入力を促す。
            'out_of_band' => $freeeCredential->isOutOfBand(),
        ]);
    }

    /**
     * OOB方式で表示された認可コードを手貼りして交換する。
     *
     * コールバックURLを登録できない環境（ローカル開発など）の唯一の経路。
     * stateはブラウザ遷移を経ないため照合できないので、代わりに
     * 「管理者本人がコードを貼っている」ことを認可の根拠にする。
     */
    public function exchangeCode(Request $request, FreeeCredential $freeeCredential): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:512'],
        ]);

        if (! $freeeCredential->isOutOfBand()) {
            throw ValidationException::withMessages([
                'message' => 'この連携はコールバックURL方式です。「認可する」から認可してください。',
            ]);
        }

        try {
            $this->tokens->exchangeAuthorizationCode($freeeCredential, trim($validated['code']), $this->activeUserId());
        } catch (FreeeReauthorizationRequiredException $exception) {
            throw ValidationException::withMessages(['message' => $exception->getMessage()]);
        }

        $this->fillCompanyName($freeeCredential);

        return response()->json([
            'message' => 'freeeとの連携が完了しました。',
            'credential' => $freeeCredential->fresh()->adminPayload(),
        ]);
    }

    /**
     * 認可時に事業所が確定しなかった場合の選択肢を返す。
     */
    public function companies(FreeeCredential $freeeCredential): JsonResponse
    {
        $this->authorizeAdmin();

        try {
            $me = $this->api->me($freeeCredential);
        } catch (FreeeReauthorizationRequiredException $exception) {
            throw ValidationException::withMessages(['message' => $exception->getMessage()]);
        }

        return response()->json([
            'companies' => $this->companyOptions($me),
        ]);
    }

    /**
     * 事業所を確定する。company_idが無いままではAPI呼び出しが全て失敗するため、
     * 認可は済んでいるが事業所が未確定な状態からの復帰手段。
     */
    public function selectCompany(Request $request, FreeeCredential $freeeCredential): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        try {
            $me = $this->api->me($freeeCredential);
        } catch (FreeeReauthorizationRequiredException $exception) {
            throw ValidationException::withMessages(['message' => $exception->getMessage()]);
        }

        $this->tokens->selectCompany(
            $freeeCredential,
            (int) $validated['company_id'],
            $this->companyOptions($me),
        );

        return response()->json([
            'message' => '事業所を設定しました。',
            'credential' => $freeeCredential->fresh()->adminPayload(),
        ]);
    }

    /**
     * freeeからのコールバック。ブラウザ遷移なのでJSONではなく管理画面へリダイレクトする。
     */
    public function callback(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $state = (string) $request->query('state', '');
        $expectedState = (string) $request->session()->pull(self::SESSION_STATE, '');
        $credentialId = $request->session()->pull(self::SESSION_CREDENTIAL);

        if ($error = $request->query('error')) {
            return $this->backToTab('error', 'freee側で認可が中断されました（'.$error.'）。');
        }

        // stateはCSRF対策。空文字同士が一致してしまわないよう明示的に弾く。
        if ($expectedState === '' || ! hash_equals($expectedState, $state)) {
            return $this->backToTab('error', '認可リクエストの照合に失敗しました。もう一度お試しください。');
        }

        $credential = $credentialId ? FreeeCredential::query()->find($credentialId) : null;

        if (! $credential) {
            return $this->backToTab('error', '対象のfreee連携設定が見つかりませんでした。');
        }

        $code = (string) $request->query('code', '');

        if ($code === '') {
            return $this->backToTab('error', '認可コードを受け取れませんでした。');
        }

        try {
            $this->tokens->exchangeAuthorizationCode($credential, $code, $this->activeUserId());
        } catch (ValidationException $exception) {
            return $this->backToTab('error', $this->firstMessage($exception));
        } catch (FreeeReauthorizationRequiredException $exception) {
            return $this->backToTab('error', $exception->getMessage());
        }

        // 事業所名は認可直後には返らないので、そのまま取得して表示用に埋める。
        $this->fillCompanyName($credential);

        return $this->backToTab('connected', 'freeeとの連携が完了しました。');
    }

    /**
     * 手動更新。連鎖が生きているかを管理者が確かめるための操作。
     */
    public function refresh(FreeeCredential $freeeCredential): JsonResponse
    {
        $this->authorizeAdmin();

        try {
            $this->tokens->refresh($freeeCredential, force: true);
        } catch (FreeeReauthorizationRequiredException $exception) {
            throw ValidationException::withMessages(['message' => $exception->getMessage()]);
        }

        return response()->json([
            'message' => 'アクセストークンを更新しました。',
            'credential' => $freeeCredential->fresh()->adminPayload(),
        ]);
    }

    /**
     * 接続確認。事業所IDが不要な /users/me を叩くので副作用がない。
     */
    public function test(FreeeCredential $freeeCredential): JsonResponse
    {
        $this->authorizeAdmin();

        try {
            $me = $this->api->me($freeeCredential);
        } catch (FreeeReauthorizationRequiredException $exception) {
            throw ValidationException::withMessages(['message' => $exception->getMessage()]);
        }

        $companies = collect($this->companyOptions($me));

        $this->fillCompanyName($freeeCredential, $companies->all());

        // /users/me だけでは人事労務の権限有無が分からないので実データも1件試す。
        $hr = $this->api->hrAccessCheck($freeeCredential);

        return response()->json([
            'message' => $hr['available']
                ? 'freee APIへの接続に成功しました。人事労務APIも利用できます。'
                : 'freeeへの接続は成功しましたが、人事労務APIが利用できません。',
            'connection' => [
                'email' => $me['email'] ?? null,
                'display_name' => $me['display_name'] ?? null,
                'company_id' => $freeeCredential->company_id,
                'companies' => $companies,
                'hr_available' => $hr['available'],
                'hr_status' => $hr['status'],
                'hr_message' => $hr['message'],
            ],
            'credential' => $freeeCredential->fresh()->adminPayload(),
        ]);
    }

    /**
     * 取引先一覧（freee会計）。
     *
     * freeeは総件数を返さないため、最終ページは分からない。次ページの有無だけを返す。
     */
    public function partners(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::in(FreeeAccountingClient::SORTABLE)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'fresh' => ['nullable', 'boolean'],
        ]);

        $credential = FreeeCredential::query()
            ->where('active', true)
            ->where('status', FreeeCredential::STATUS_CONNECTED)
            ->orderBy('id')
            ->first();

        if (! $credential) {
            throw ValidationException::withMessages([
                'message' => '連携済みのfreee設定がありません。先にfreeeタブで認可してください。',
            ]);
        }

        try {
            $result = $this->accounting->partners(
                $credential,
                (int) ($validated['page'] ?? 1),
                FreeeAccountingClient::DEFAULT_PER_PAGE,
                $validated['keyword'] ?? null,
                // freeeは常にid昇順（古い順）で返すため、既定は新しい順にする。
                $validated['sort'] ?? 'id',
                $validated['direction'] ?? 'desc',
                filter_var($validated['fresh'] ?? false, FILTER_VALIDATE_BOOL),
            );
        } catch (FreeeReauthorizationRequiredException $exception) {
            throw ValidationException::withMessages(['message' => $exception->getMessage()]);
        }

        return response()->json([
            'partners' => collect($result['partners']),
            'meta' => [
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total_count' => $result['total_count'],
                'last_page' => $result['last_page'],
                'has_more' => $result['has_more'],
                'from' => $result['from'],
                'to' => $result['to'],
                'sort' => $result['sort'],
                'direction' => $result['direction'],
                'sortable' => FreeeAccountingClient::SORTABLE,
            ],
        ]);
    }

    /**
     * プロジェクトをfreeeの部門と連携する。
     *
     * 既存部門があれば紐付けるだけ（freeeへの書き込み無し）、無ければ新規作成する。
     */
    public function syncSection(ProjectRecord $project): JsonResponse
    {
        $this->authorizeAdmin();

        $result = $this->sections->sync($this->connectedCredential(), $project);

        return response()->json($result + ['project' => $this->projectFreeePayload($project->fresh())]);
    }

    /**
     * 連携先の部門が実在するか・名称が一致しているかを確認する。
     */
    public function checkSection(ProjectRecord $project): JsonResponse
    {
        $this->authorizeAdmin();

        $result = $this->sections->check($this->connectedCredential(), $project);

        return response()->json($result + ['project' => $this->projectFreeePayload($project->fresh())]);
    }

    /**
     * 連携解除。freeeへのDELETEは行わない（紐付けを外すだけ）。
     */
    public function unlinkSection(ProjectRecord $project): JsonResponse
    {
        $this->authorizeAdmin();

        $this->sections->unlink($project);

        return response()->json([
            'message' => '連携を解除しました。freee側の部門は削除していません。',
            'project' => $this->projectFreeePayload($project->fresh()),
        ]);
    }

    private function projectFreeePayload(ProjectRecord $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'freee_section_id' => $project->freee_section_id,
            'freee_synced_at' => $project->freee_synced_at?->toISOString(),
        ];
    }

    /**
     * 部門連携に使う連携済み資格情報。
     */
    private function connectedCredential(): FreeeCredential
    {
        $credential = FreeeCredential::query()
            ->where('active', true)
            ->where('status', FreeeCredential::STATUS_CONNECTED)
            ->orderBy('id')
            ->first();

        if (! $credential) {
            throw ValidationException::withMessages([
                'message' => '連携済みのfreee設定がありません。先にfreeeタブで認可してください。',
            ]);
        }

        return $credential;
    }

    /**
     * 連携解除。アプリ資格情報は残すので、再認可はボタン一つで済む。
     */
    public function disconnect(FreeeCredential $freeeCredential): JsonResponse
    {
        $this->authorizeAdmin();

        $this->tokens->disconnect($freeeCredential);

        return response()->json([
            'message' => 'freee連携を解除しました。',
            'credential' => $freeeCredential->fresh()->adminPayload(),
        ]);
    }

    private function validatedCredential(Request $request, ?FreeeCredential $credential = null): array
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:1000'],
            // 通常はコールバックURL。コールバックを受けられない環境ではOOBの固定値を許す。
            'redirect_uri' => [
                // 列が varchar(255) なので上限を合わせる。コールバックURLには十分。
                'required', 'string', 'max:255',
                function (string $attribute, mixed $value, callable $fail) {
                    if ($value === FreeeCredential::OOB_REDIRECT_URI) {
                        return;
                    }

                    if (! filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail('コールバックURLは有効なURL、または '.FreeeCredential::OOB_REDIRECT_URI.' を指定してください。');
                    }
                },
            ],
            'active' => ['required', Rule::in([true, false, 0, 1, '0', '1'])],
        ]);

        // 空のシークレットは「変更なし」を意味する。既存値を消してしまわない。
        if (! filled($validated['client_secret'] ?? null)) {
            unset($validated['client_secret']);
        }

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOL);

        if ($credential === null && ! filled($validated['client_secret'] ?? null)) {
            throw ValidationException::withMessages([
                'message' => '新規登録ではクライアントシークレットが必須です。',
            ]);
        }

        return $validated;
    }

    /**
     * /users/me のレスポンスを事業所の選択肢に整える。
     *
     * @return array<int, array{id: int|null, name: string|null, role: string|null}>
     */
    private function companyOptions(array $me): array
    {
        return collect($me['companies'] ?? [])
            ->map(fn ($company) => [
                'id' => isset($company['id']) ? (int) $company['id'] : null,
                'name' => $company['name'] ?? ($company['display_name'] ?? null),
                'role' => $company['role'] ?? null,
            ])
            ->filter(fn (array $company) => $company['id'] !== null)
            ->values()
            ->all();
    }

    /**
     * 表示用の事業所名を埋める。取得に失敗しても連携自体は成立しているので黙って諦める。
     */
    private function fillCompanyName(FreeeCredential $credential, ?array $companies = null): void
    {
        if (! filled($credential->company_id)) {
            return;
        }

        try {
            $companies ??= $this->companyOptions($this->api->me($credential));
        } catch (\Throwable) {
            return;
        }

        $match = collect($companies)->firstWhere('id', $credential->company_id);

        if ($match && filled($match['name'] ?? null)) {
            $credential->forceFill(['company_name' => $match['name']])->save();
        }
    }

    private function backToTab(string $result, string $message): RedirectResponse
    {
        return redirect()->to(self::ADMIN_TAB_PATH.'?'.http_build_query([
            'freee_result' => $result,
            'freee_message' => $message,
        ]));
    }

    private function firstMessage(ValidationException $exception): string
    {
        return collect($exception->errors())->flatten()->first()
            ?? 'freee連携に失敗しました。';
    }

    private function activeUserId(): int
    {
        // Double-account dropped (community_logic): the authenticated user only.
        $user = Auth::user();
        abort_unless($user, 401, '認証が必要です。');

        return (int) $user->id;
    }

    private function authorizeAdmin(): void
    {
        // Community-aware admin gate (was hardcoded ADMIN_USER_IDS).
        $user = Auth::user();
        abort_unless($user, 401, '認証が必要です。');

        abort_unless($user->isAdmin(), 403, '管理者権限がありません。');
    }
}
