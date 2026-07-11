<?php

namespace Tests\Unit;

use App\Models\ZoomAccount;
use App\Services\ZoomApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ZoomApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_admin_payload_never_contains_secrets(): void
    {
        $account = $this->configuredAccount();

        $payload = $account->adminPayload();

        $this->assertArrayNotHasKey('client_secret', $payload);
        $this->assertArrayNotHasKey('host_password', $payload);
        $this->assertArrayNotHasKey('webhook_secret', $payload);
        $this->assertTrue($payload['api_configured']);
        $this->assertTrue($payload['host_password_configured']);
        $this->assertTrue($payload['webhook_secret_configured']);
    }

    public function test_unconfigured_active_account_remains_selectable_in_calendar(): void
    {
        $account = new ZoomAccount([
            'slot' => 1,
            'label' => 'Zoom2',
            'active' => true,
        ]);

        $this->assertFalse($account->isApiConfigured());
        $this->assertSame([
            'label' => 'Zoom2',
            'value' => 1,
            'selected' => false,
            'selectable' => true,
        ], $account->calendarOption());
    }

    public function test_inactive_account_remains_visible_but_not_selectable_in_calendar(): void
    {
        $account = new ZoomAccount([
            'slot' => 2,
            'label' => 'Zoom3',
            'active' => false,
        ]);

        $this->assertFalse($account->calendarOption()['selectable']);
    }

    public function test_access_token_is_requested_with_account_credentials_and_cached(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'access_token' => 'zoom-access-token',
                'expires_in' => 3600,
            ]),
        ]);

        $service = app(ZoomApiService::class);
        $account = $this->configuredAccount();

        $this->assertSame('zoom-access-token', $service->accessToken($account));
        $this->assertSame('zoom-access-token', $service->accessToken($account));

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://zoom.us/oauth/token'
            && $request['grant_type'] === 'account_credentials'
            && $request['account_id'] === 'account-id'
            && $request->hasHeader('Authorization'));
    }

    public function test_connection_check_uses_the_configured_host_meetings_endpoint(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'access_token' => 'zoom-access-token',
                'expires_in' => 3600,
            ]),
            'https://api.zoom.us/v2/users/zoom1%40example.com/meetings*' => Http::response([
                'total_records' => 2,
                'meetings' => [],
            ]),
        ]);

        $result = app(ZoomApiService::class)->testConnection($this->configuredAccount());

        $this->assertSame('zoom1@example.com', $result['host_email']);
        $this->assertSame(2, $result['total_records']);
        Http::assertSent(fn ($request) => str_starts_with(
            $request->url(),
            'https://api.zoom.us/v2/users/zoom1%40example.com/meetings'
        ) && $request['type'] === 'scheduled' && $request['page_size'] === 1);
    }

    public function test_draft_connection_check_does_not_reuse_a_cached_token(): void
    {
        $tokenSequence = Http::sequence()
            ->push(['access_token' => 'cached-token', 'expires_in' => 3600])
            ->push(['access_token' => 'fresh-token', 'expires_in' => 3600]);
        Http::fake([
            'https://zoom.us/oauth/token' => $tokenSequence,
            'https://api.zoom.us/v2/users/zoom1%40example.com/meetings*' => Http::response([
                'total_records' => 0,
                'meetings' => [],
            ]),
        ]);

        $service = app(ZoomApiService::class);
        $account = $this->configuredAccount();
        $service->accessToken($account);
        $service->testConnection($account, false);

        Http::assertSentCount(3);
    }

    public function test_oauth_error_includes_the_actual_zoom_response(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'error' => 'invalid_client',
                'reason' => 'Invalid client_id or client_secret',
            ], 401),
        ]);

        try {
            app(ZoomApiService::class)->accessToken($this->configuredAccount(), false);
            $this->fail('A validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $message = $exception->errors()['message'][0];
            $this->assertStringContainsString('HTTP 401', $message);
            $this->assertStringContainsString('invalid_client', $message);
            $this->assertStringContainsString('Invalid client_id or client_secret', $message);
        }
    }

    public function test_meeting_permission_error_includes_the_actual_zoom_response(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'access_token' => 'zoom-access-token',
                'expires_in' => 3600,
            ]),
            'https://api.zoom.us/v2/users/zoom1%40example.com/meetings*' => Http::response([
                'code' => 4711,
                'message' => 'Invalid access token, does not contain meeting scopes.',
            ], 400),
        ]);

        try {
            app(ZoomApiService::class)->testConnection($this->configuredAccount(), false);
            $this->fail('A validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $message = $exception->errors()['message'][0];
            $this->assertStringContainsString('HTTP 400', $message);
            $this->assertStringContainsString('4711', $message);
            $this->assertStringContainsString('does not contain meeting scopes', $message);
        }
    }

    private function configuredAccount(): ZoomAccount
    {
        $account = new ZoomAccount;
        $account->id = 1;
        $account->slot = 0;
        $account->label = 'Zoom1';
        $account->host_email = 'zoom1@example.com';
        $account->host_password = 'host-password';
        $account->account_id = 'account-id';
        $account->client_id = 'client-id';
        $account->client_secret = 'client-secret';
        $account->webhook_secret = 'webhook-secret';
        $account->active = true;

        return $account;
    }
}
