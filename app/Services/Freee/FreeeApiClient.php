<?php

namespace App\Services\Freee;

use App\Models\FreeeCredential;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * freee人事労務APIのクライアント。
 * 認証・401の拾い直し・エラー整形は FreeeBaseClient に持たせている。
 */
class FreeeApiClient extends FreeeBaseClient
{
    private const HR_BASE_URL = 'https://api.freee.co.jp/hr';

    protected function baseUrl(): string
    {
        return self::HR_BASE_URL;
    }

    protected function productLabel(): string
    {
        return 'freee人事労務';
    }

    /**
     * 認可済みユーザー自身と、所属事業所の一覧。事業所IDが不要なので接続確認に使える。
     */
    public function me(FreeeCredential $credential): array
    {
        return $this->get($credential, '/api/v1/users/me', [], withCompany: false);
    }

    /**
     * 人事労務APIが実際に使えるかを確かめる。
     *
     * /users/me は default_read だけで200を返すため、接続確認に使うと
     * 「アプリに人事労務の権限が無い」状態でも成功に見えてしまう。
     * 実データのエンドポイントを1件だけ叩いて権限の有無を判定する。
     *
     * @return array{available: bool, status: int|null, message: string|null}
     */
    public function hrAccessCheck(FreeeCredential $credential): array
    {
        try {
            $companyId = $this->companyId($credential);
            $token = $this->tokens->accessToken($credential);

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->get($this->baseUrl().'/api/v1/employees', [
                    'company_id' => $companyId,
                    'year' => (int) now()->year,
                    'month' => (int) now()->month,
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                return ['available' => true, 'status' => $response->status(), 'message' => null];
            }

            $payload = $response->json();
            $payload = is_array($payload) ? $payload : [];

            $message = $response->status() === 403
                ? 'freeeアプリに人事労務APIの権限がありません。アプリ管理で人事労務の権限を追加し、再認可してください。'
                : $this->clean((string) ($payload['message'] ?? $payload['error'] ?? $response->body()));

            return ['available' => false, 'status' => $response->status(), 'message' => $message];
        } catch (RequestException|ConnectionException $exception) {
            return [
                'available' => false,
                'status' => $exception instanceof RequestException ? $exception->response?->status() : null,
                'message' => $this->clean($exception->getMessage()),
            ];
        } catch (ValidationException $exception) {
            return [
                'available' => false,
                'status' => null,
                'message' => collect($exception->errors())->flatten()->first(),
            ];
        }
    }

    /**
     * 従業員一覧。freeeは基準年月ごとのスナップショットとして返す。
     */
    public function employees(FreeeCredential $credential, int $year, int $month, int $limit = 50, int $offset = 0): array
    {
        return $this->get($credential, '/api/v1/employees', [
            'year' => $year,
            'month' => $month,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }
}
