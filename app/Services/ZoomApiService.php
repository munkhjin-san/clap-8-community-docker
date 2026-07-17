<?php

namespace App\Services;

use App\Models\ZoomAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ZoomApiService
{
    public function accountForSlot(int $slot, bool $requireActive = true): ZoomAccount
    {
        $query = ZoomAccount::query()->where('slot', $slot);

        if ($requireActive) {
            $query->where('active', true);
        }

        $account = $query->first();

        if (! $account || ! $account->isApiConfigured()) {
            throw ValidationException::withMessages([
                'message' => 'Zoomアカウントが未設定または無効です。管理画面で設定を確認してください。',
            ]);
        }

        return $account;
    }

    public function accessToken(ZoomAccount $account, bool $useCache = true): string
    {
        if (! $account->isApiConfigured()) {
            throw ValidationException::withMessages([
                'message' => 'Zoom API認証情報が不足しています。',
            ]);
        }

        $cacheKey = "zoom:access-token:{$account->id}:".hash('sha256', (string) $account->client_id);

        $requestToken = function () use ($account) {
            try {
                $response = Http::asForm()
                    ->withBasicAuth((string) $account->client_id, (string) $account->client_secret)
                    ->timeout(15)
                    ->post('https://zoom.us/oauth/token', [
                        'grant_type' => 'account_credentials',
                        'account_id' => $account->account_id,
                    ])
                    ->throw();
            } catch (RequestException $exception) {
                Log::warning('Zoom access token request failed.', [
                    'zoom_account_id' => $account->id,
                    'status' => $exception->response?->status(),
                ]);

                throw ValidationException::withMessages([
                    'message' => $this->zoomErrorMessage($exception, 'OAuth認証'),
                ]);
            } catch (ConnectionException $exception) {
                Log::warning('Zoom access token connection failed.', [
                    'zoom_account_id' => $account->id,
                ]);

                throw ValidationException::withMessages([
                    'message' => 'Zoom OAuth接続エラー：'.$this->cleanErrorDetail($exception->getMessage()),
                ]);
            }

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw ValidationException::withMessages([
                    'message' => 'Zoom APIからアクセストークンが返されませんでした。',
                ]);
            }

            return $token;
        };

        return $useCache
            ? Cache::remember($cacheKey, now()->addMinutes(55), $requestToken)
            : $requestToken();
    }

    public function testConnection(ZoomAccount $account, bool $useCachedToken = true): array
    {
        $token = $this->accessToken($account, $useCachedToken);

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->get(
                    'https://api.zoom.us/v2/users/'.rawurlencode((string) $account->host_email).'/meetings',
                    [
                        'type' => 'scheduled',
                        'page_size' => 1,
                    ]
                )
                ->throw();
        } catch (RequestException $exception) {
            Log::warning('Zoom account connection test failed.', [
                'zoom_account_id' => $account->id,
                'status' => $exception->response?->status(),
            ]);

            throw ValidationException::withMessages([
                'message' => $this->zoomErrorMessage($exception, 'ミーティング権限確認'),
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Zoom account connection test could not connect.', [
                'zoom_account_id' => $account->id,
            ]);

            throw ValidationException::withMessages([
                'message' => 'Zoom API接続エラー：'.$this->cleanErrorDetail($exception->getMessage()),
            ]);
        }

        return [
            'host_email' => $account->host_email,
            'total_records' => (int) $response->json('total_records', 0),
        ];
    }

    public function forgetToken(ZoomAccount $account): void
    {
        Cache::forget("zoom:access-token:{$account->id}:".hash('sha256', (string) $account->client_id));
    }

    private function zoomErrorMessage(RequestException $exception, string $operation): string
    {
        $response = $exception->response;
        $payload = $response?->json();
        $payload = is_array($payload) ? $payload : [];
        $code = $payload['code'] ?? $payload['error'] ?? null;
        $detail = $payload['message']
            ?? $payload['reason']
            ?? $payload['error_description']
            ?? $payload['error']
            ?? $response?->body()
            ?? $exception->getMessage();

        if (is_array($detail) || is_object($detail)) {
            $detail = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $metadata = array_filter([
            $response ? 'HTTP '.$response->status() : null,
            filled($code) ? (string) $code : null,
        ]);
        $suffix = $metadata === [] ? '' : '（'.implode(' / ', $metadata).'）';

        return 'Zoom '.$operation.'エラー'.$suffix.'：'.$this->cleanErrorDetail((string) $detail);
    }

    private function cleanErrorDetail(string $detail): string
    {
        $detail = preg_replace('/\s+/u', ' ', strip_tags($detail)) ?? $detail;

        return mb_substr(trim($detail), 0, 1000);
    }
}
