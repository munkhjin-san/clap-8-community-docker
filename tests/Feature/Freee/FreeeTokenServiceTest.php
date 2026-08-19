<?php

namespace Tests\Feature\Freee;

use App\Models\FreeeCredential;
use App\Services\Freee\FreeeReauthorizationRequiredException;
use App\Services\Freee\FreeeTokenService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * リフレッシュトークン連鎖の不変条件を検証する。
 * ここが壊れると「人が再認可するまで連携が死ぬ」ので、挙動を固定しておく。
 */
class FreeeTokenServiceTest extends TestCase
{
    private FreeeTokenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('freee_credentials', function ($table) {
            $table->increments('id');
            $table->string('label');
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->string('redirect_uri')->nullable();
            $table->integer('company_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('external_cid')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('token_type')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->integer('refresh_count')->default(0);
            $table->string('status')->default('unconfigured');
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->integer('authorized_by')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $this->service = app(FreeeTokenService::class);
    }

    private function credential(array $overrides = []): FreeeCredential
    {
        return FreeeCredential::query()->create(array_merge([
            'label' => 'テスト事業所',
            'client_id' => 'client-abc',
            'client_secret' => 'secret-abc',
            'redirect_uri' => 'https://example.test/admin/freee/callback',
            'active' => true,
        ], $overrides));
    }

    private function connectedCredential(array $overrides = []): FreeeCredential
    {
        return $this->credential(array_merge([
            'company_id' => 1234567,
            'access_token' => 'access-old',
            'refresh_token' => 'refresh-old',
            'status' => FreeeCredential::STATUS_CONNECTED,
            'access_token_expires_at' => now()->addHours(6),
            'refresh_token_expires_at' => now()->addDays(90),
        ], $overrides));
    }

    private function tokenResponse(array $overrides = []): array
    {
        return array_merge([
            'access_token' => 'access-new',
            'refresh_token' => 'refresh-new',
            'token_type' => 'bearer',
            'expires_in' => 21600,
            'scope' => 'read write default_read',
            'company_id' => 1234567,
            'external_cid' => 'ext-1',
        ], $overrides);
    }

    public function test_authorization_code_exchange_stores_the_token_pair_and_both_expiries(): void
    {
        Http::fake(['*/public_api/token' => Http::response($this->tokenResponse())]);
        // マイクロ秒を含まない時刻に固定する。DB保存時に切り捨てられて1秒ずれるのを避ける。
        $this->travelTo(now()->startOfSecond());

        $credential = $this->credential();
        $this->service->exchangeAuthorizationCode($credential, 'auth-code-1', authorizedByUserId: 42);

        $credential->refresh();

        $this->assertSame('access-new', $credential->access_token);
        $this->assertSame('refresh-new', $credential->refresh_token);
        $this->assertSame(FreeeCredential::STATUS_CONNECTED, $credential->status);
        $this->assertSame(1234567, $credential->company_id);
        $this->assertSame(42, (int) $credential->authorized_by);
        // アクセストークンは6時間、リフレッシュトークンは90日。
        $this->assertSame(21600, (int) now()->diffInSeconds($credential->access_token_expires_at, false));
        $this->assertSame(90, (int) now()->startOfDay()->diffInDays($credential->refresh_token_expires_at->startOfDay(), false));
    }

    public function test_a_full_length_scope_list_does_not_break_the_token_write(): void
    {
        // 実際にfreeeが返したスコープ（会計のみで約900文字）。
        // 以前はscope列に保存していたため 1406 Data too long で認可が失敗した。
        // 現在は保存しないので、どれだけ長くても認可は通る。
        $scope = implode(' ', [
            'accounting:account_groups:write', 'accounting:account_items:read',
            'accounting:account_items:write', 'accounting:approval_flow_routes:read',
            'accounting:approval_request_forms:read', 'accounting:approval_requests:read',
            'accounting:approval_requests:write', 'accounting:companies:read',
            'accounting:companies:write', 'accounting:deals:read', 'accounting:deals:write',
            'accounting:docs:read', 'accounting:expense_application_templates:read',
            'accounting:expense_application_templates:write',
            'accounting:expense_applications:read', 'accounting:expense_applications:write',
            'accounting:items:read', 'accounting:manual_journals:read',
            'accounting:partners:read', 'accounting:purchase_requests:read',
            'accounting:purchase_requests:write', 'accounting:receipts:read',
            'accounting:sections:read', 'accounting:users:read', 'default_read',
        ]);

        $this->assertGreaterThan(255, strlen($scope), 'このテストはVARCHAR(255)を超える長さで意味を持つ');

        Http::fake([
            '*/public_api/token' => Http::response($this->tokenResponse(['scope' => $scope])),
        ]);

        $credential = $this->credential();
        $this->service->exchangeAuthorizationCode($credential, 'auth-code-1');

        $credential->refresh();
        $this->assertTrue($credential->isConnected());
        $this->assertSame('access-new', $credential->access_token);
        // scopeは保存しない。列自体が無いので属性としても存在しないこと。
        $this->assertArrayNotHasKey('scope', $credential->getAttributes());
        $this->assertArrayNotHasKey('scope', $credential->adminPayload());
    }

    public function test_secrets_are_encrypted_at_rest(): void
    {
        $credential = $this->connectedCredential();

        $raw = DB::table('freee_credentials')->where('id', $credential->id)->first();

        $this->assertNotSame('secret-abc', $raw->client_secret);
        $this->assertNotSame('refresh-old', $raw->refresh_token);
        $this->assertNotSame('access-old', $raw->access_token);
        // 復号して読めることも確認する。
        $this->assertSame('refresh-old', $credential->fresh()->refresh_token);
    }

    public function test_refresh_rotates_the_refresh_token_and_resets_the_ninety_day_window(): void
    {
        Http::fake(['*/public_api/token' => Http::response($this->tokenResponse())]);

        // 89日前に発行され、残り1日のリフレッシュトークン。
        $credential = $this->connectedCredential([
            'refresh_token_expires_at' => now()->addDay(),
            'access_token_expires_at' => now()->addMinutes(5),
        ]);

        $this->service->refresh($credential);
        $credential->refresh();

        $this->assertSame('access-new', $credential->access_token);
        // 単回使用なので古いリフレッシュトークンは残っていてはいけない。
        $this->assertSame('refresh-new', $credential->refresh_token);
        $this->assertSame(1, $credential->refresh_count);
        $this->assertNotNull($credential->last_refreshed_at);
        // 更新で90日に戻る。これが「使い続ければ人の操作は不要」の根拠。
        $this->assertSame(90, (int) now()->startOfDay()->diffInDays($credential->refresh_token_expires_at->startOfDay(), false));

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'refresh-old');
    }

    public function test_access_token_is_reused_while_still_fresh_and_refreshed_when_near_expiry(): void
    {
        // このテストは期限判定だけを見る。事業所は更新で変わらないので返させない。
        Http::fake([
            '*/public_api/token' => Http::response($this->tokenResponse(['company_id' => null])),
        ]);

        // 余裕があるうちはネットワークに出ない。
        $fresh = $this->connectedCredential(['access_token_expires_at' => now()->addHours(5)]);
        $this->assertSame('access-old', $this->service->accessToken($fresh));
        Http::assertNothingSent();

        // 期限切れ前でも猶予（30分）を切ったら先に更新する。
        $stale = $this->connectedCredential([
            'company_id' => 7654321,
            'access_token_expires_at' => now()->addMinutes(10),
        ]);
        $this->assertSame('access-new', $this->service->accessToken($stale));
        Http::assertSentCount(1);
    }

    public function test_a_second_caller_reuses_the_rotated_token_instead_of_replaying_the_used_one(): void
    {
        Http::fake(['*/public_api/token' => Http::response($this->tokenResponse())]);

        $credential = $this->connectedCredential(['access_token_expires_at' => now()->addMinutes(5)]);

        // 同じ行を指す2つ目のインスタンス（別プロセス相当）。更新前の状態を握っている。
        $stale = FreeeCredential::query()->find($credential->id);

        $this->service->refresh($credential);
        $this->service->refresh($stale);

        // 2度目はロック内の読み直しで「もう新しい」と判断し、HTTPを叩かない。
        // ここが効かないと使用済みリフレッシュトークンを再送して連鎖が切れる。
        Http::assertSentCount(1);
        $this->assertSame('access-new', $stale->access_token);
        $this->assertSame(1, $credential->fresh()->refresh_count);
    }

    public function test_invalid_grant_marks_the_credential_as_needing_reauthorization(): void
    {
        Http::fake([
            '*/public_api/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => '指定された認可グラントは不正か、有効期限切れか、無効です。',
            ], 401),
        ]);

        $credential = $this->connectedCredential(['access_token_expires_at' => now()->addMinutes(5)]);

        try {
            $this->service->refresh($credential);
            $this->fail('FreeeReauthorizationRequiredException が投げられるべきです。');
        } catch (FreeeReauthorizationRequiredException $exception) {
            $this->assertSame($credential->id, $exception->credential->id);
        }

        $credential->refresh();
        $this->assertSame(FreeeCredential::STATUS_NEEDS_REAUTH, $credential->status);
        $this->assertTrue($credential->reauthorizationRequired());
        // freeeが返す説明文をそのまま残す（管理者が原因を読めるようにする）。
        $this->assertStringContainsString('HTTP 401', $credential->last_error);
        $this->assertStringContainsString('有効期限切れ', $credential->last_error);
        // 失敗しても同じトークンで再送していないこと。
        Http::assertSentCount(1);
    }

    public function test_expired_refresh_token_fails_fast_without_calling_freee(): void
    {
        Http::fake(['*/public_api/token' => Http::response($this->tokenResponse())]);

        $credential = $this->connectedCredential([
            'refresh_token_expires_at' => now()->subDay(),
            'access_token_expires_at' => now()->subHour(),
        ]);

        $this->expectException(FreeeReauthorizationRequiredException::class);

        try {
            $this->service->refresh($credential);
        } finally {
            // 期限切れが分かっているなら投げる前に諦める（無駄な消費をしない）。
            Http::assertNothingSent();
            $this->assertSame(FreeeCredential::STATUS_NEEDS_REAUTH, $credential->fresh()->status);
        }
    }

    public function test_missing_refresh_token_in_response_is_rejected(): void
    {
        // リフレッシュトークンが返らないと連鎖を維持できないため、成功扱いにしてはいけない。
        Http::fake([
            '*/public_api/token' => Http::response($this->tokenResponse(['refresh_token' => null])),
        ]);

        $credential = $this->credential();

        $this->expectException(ValidationException::class);

        $this->service->exchangeAuthorizationCode($credential, 'auth-code-1');
    }

    public function test_disconnect_clears_tokens_but_keeps_app_credentials(): void
    {
        $credential = $this->connectedCredential();

        $this->service->disconnect($credential);
        $credential->refresh();

        $this->assertNull($credential->access_token);
        $this->assertNull($credential->refresh_token);
        $this->assertNull($credential->company_id);
        $this->assertFalse($credential->isConnected());
        // 再認可がボタン一つで済むように、アプリ資格情報は残す。
        $this->assertSame('client-abc', $credential->client_id);
        $this->assertSame('secret-abc', $credential->client_secret);
        $this->assertTrue($credential->isAppConfigured());
    }

    public function test_routine_refresh_of_the_same_company_is_not_blocked_by_the_duplicate_guard(): void
    {
        // 同じ事業所IDが返る通常の更新。重複検査に引っかかって止まってはいけない。
        Http::fake(['*/public_api/token' => Http::response($this->tokenResponse())]);

        $credential = $this->connectedCredential([
            'company_id' => 1234567,
            'access_token_expires_at' => now()->addMinutes(5),
        ]);

        $this->service->refresh($credential);
        $credential->refresh();

        $this->assertSame('access-new', $credential->access_token);
        $this->assertSame(1234567, $credential->company_id);
        $this->assertSame(FreeeCredential::STATUS_CONNECTED, $credential->status);
    }

    public function test_connecting_a_company_already_linked_elsewhere_is_rejected_with_a_readable_message(): void
    {
        // 既に事業所1234567を連携済みの行がある。
        $this->connectedCredential(['label' => '既存の連携']);

        Http::fake(['*/public_api/token' => Http::response($this->tokenResponse())]);

        // 2行目で同じ事業所を選んでしまうのは初回セットアップでよくある操作。
        $second = $this->credential(['label' => '2つ目の連携']);

        try {
            $this->service->exchangeAuthorizationCode($second, 'auth-code-2');
            $this->fail('重複した事業所は拒否されるべきです。');
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            // UNIQUE制約違反（500）ではなく、原因が読めるメッセージで返ること。
            $this->assertStringContainsString('1234567', $message);
            $this->assertStringContainsString('既存の連携', $message);
        }

        // 半端に接続済みにせず、未認可のまま残す。
        $second->refresh();
        $this->assertNull($second->company_id);
        $this->assertFalse($second->isConnected());
    }

    public function test_company_can_be_selected_when_authorization_did_not_return_one(): void
    {
        // company_id を返さない認可（全事業所付与の旧URL形式など）。
        Http::fake([
            '*/public_api/token' => Http::response($this->tokenResponse(['company_id' => null])),
        ]);

        $credential = $this->credential();
        $this->service->exchangeAuthorizationCode($credential, 'auth-code-1');
        $credential->refresh();

        // トークンはあるが事業所が無い＝API呼び出しが全て失敗する状態。
        $this->assertTrue($credential->isConnected());
        $this->assertTrue($credential->awaitingCompanySelection());

        $this->service->selectCompany($credential, 222, [
            ['id' => 111, 'name' => '本社'],
            ['id' => 222, 'name' => '大阪支社'],
        ]);
        $credential->refresh();

        $this->assertSame(222, $credential->company_id);
        $this->assertSame('大阪支社', $credential->company_name);
        $this->assertFalse($credential->awaitingCompanySelection());
    }

    public function test_selecting_a_company_outside_the_authorized_account_is_rejected(): void
    {
        $credential = $this->connectedCredential(['company_id' => null]);

        $this->expectException(ValidationException::class);

        $this->service->selectCompany($credential, 999, [
            ['id' => 111, 'name' => '本社'],
        ]);
    }

    public function test_out_of_band_credentials_are_recognised(): void
    {
        $callback = $this->credential();
        $this->assertFalse($callback->isOutOfBand());

        $oob = $this->credential([
            'label' => 'ローカル検証',
            'redirect_uri' => FreeeCredential::OOB_REDIRECT_URI,
        ]);
        $this->assertTrue($oob->isOutOfBand());

        // OOBでも認可URLは組み立てられる（freeeがコードを画面表示する）。
        $url = $this->service->authorizationUrl($oob, 'state-oob');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(FreeeCredential::OOB_REDIRECT_URI, $query['redirect_uri']);
    }

    public function test_authorization_url_contains_the_required_oauth_parameters(): void
    {
        $credential = $this->credential();

        $url = $this->service->authorizationUrl($credential, 'state-xyz');

        $this->assertStringStartsWith('https://accounts.secure.freee.co.jp/public_api/authorize?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('client-abc', $query['client_id']);
        $this->assertSame('https://example.test/admin/freee/callback', $query['redirect_uri']);
        $this->assertSame('state-xyz', $query['state']);
        $this->assertSame('select_company', $query['prompt']);
    }
}
