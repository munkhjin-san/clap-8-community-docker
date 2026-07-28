<?php

namespace App\Services;

use App\Models\ZoomAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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

    public function meetingTranscriptMetadata(ZoomAccount $account, string $meetingUuid): array
    {
        $token = $this->accessToken($account);
        $encodedMeetingUuid = $this->encodeMeetingUuid($meetingUuid);

        try {
            return Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->retry(3, 1000)
                ->get("https://api.zoom.us/v2/meetings/{$encodedMeetingUuid}/transcript")
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            Log::warning('Zoom transcript metadata request failed.', [
                'zoom_account_id' => $account->id,
                'status' => $exception->response?->status(),
            ]);

            throw new RuntimeException(
                $this->zoomErrorMessage($exception, '文字起こし取得'),
                previous: $exception,
            );
        } catch (ConnectionException $exception) {
            Log::warning('Zoom transcript metadata connection failed.', [
                'zoom_account_id' => $account->id,
            ]);

            throw new RuntimeException(
                'Zoom文字起こし接続エラー：'.$this->cleanErrorDetail($exception->getMessage()),
                previous: $exception,
            );
        }
    }

    public function downloadMeetingTranscript(ZoomAccount $account, string $downloadUrl): string
    {
        $token = $this->accessToken($account);

        try {
            return Http::withToken($token)
                ->accept('text/vtt, text/plain')
                ->timeout(120)
                ->retry(3, 1000)
                ->get($downloadUrl)
                ->throw()
                ->body();
        } catch (RequestException $exception) {
            Log::warning('Zoom transcript download failed.', [
                'zoom_account_id' => $account->id,
                'status' => $exception->response?->status(),
            ]);

            throw new RuntimeException(
                $this->zoomErrorMessage($exception, '文字起こしダウンロード'),
                previous: $exception,
            );
        } catch (ConnectionException $exception) {
            Log::warning('Zoom transcript download connection failed.', [
                'zoom_account_id' => $account->id,
            ]);

            throw new RuntimeException(
                'Zoom文字起こしダウンロード接続エラー：'.$this->cleanErrorDetail($exception->getMessage()),
                previous: $exception,
            );
        }
    }

    public function forgetToken(ZoomAccount $account): void
    {
        Cache::forget("zoom:access-token:{$account->id}:".hash('sha256', (string) $account->client_id));
    }

    private function encodeMeetingUuid(string $meetingUuid): string
    {
        $encoded = rawurlencode($meetingUuid);

        if (str_starts_with($meetingUuid, '/') || str_contains($meetingUuid, '//')) {
            return rawurlencode($encoded);
        }

        return $encoded;
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
