<?php

namespace App\Services\Freee;

use App\Models\FreeeCredential;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * freee APIクライアントの共通部分。
 *
 * freeeはプロダクトごとにベースURLとパスのプレフィックスが違う（人事労務は /hr + /api/v1、
 * 会計は / + /api/1）が、認証・401の拾い直し・エラー整形は完全に同じ。
 * トークンの取得と更新は FreeeTokenService に一任する。
 */
abstract class FreeeBaseClient
{
    public function __construct(protected readonly FreeeTokenService $tokens) {}

    /** プロダクトのベースURL（末尾スラッシュなし）。 */
    abstract protected function baseUrl(): string;

    /** エラーメッセージに出すプロダクト名。 */
    abstract protected function productLabel(): string;

    /**
     * GETリクエスト。$withCompany が true なら company_id を自動で付与する。
     */
    public function get(FreeeCredential $credential, string $path, array $query = [], bool $withCompany = true): array
    {
        if ($withCompany) {
            $query['company_id'] ??= $this->companyId($credential);
        }

        $response = $this->send($credential, $path, $query);
        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    /**
     * POSTリクエスト。GETと違い副作用があるため、401の拾い直しは1回だけに厳格に留める。
     */
    public function post(FreeeCredential $credential, string $path, array $body): array
    {
        $response = $this->sendJson($credential, 'post', $path, $body);
        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    /**
     * PUTリクエスト。POSTと同じく401の拾い直しは1回だけ。
     */
    public function put(FreeeCredential $credential, string $path, array $body): array
    {
        $response = $this->sendJson($credential, 'put', $path, $body);
        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    /**
     * 呼び出しに使う事業所ID。
     *
     * 設定（FREEE_COMPANY_ID）があればそれを優先し、無ければ認可時にfreeeが返した値を使う。
     * どちらも無い場合はAPIを叩いても必ず失敗するので、ここで止める。
     */
    protected function companyId(FreeeCredential $credential): int
    {
        $configured = config('services.freee.company_id');

        if (filled($configured)) {
            return (int) $configured;
        }

        if (filled($credential->company_id)) {
            return (int) $credential->company_id;
        }

        throw ValidationException::withMessages([
            'message' => 'freeeの事業所IDが未設定です。認可をやり直すか、FREEE_COMPANY_IDを設定してください。',
        ]);
    }

    /**
     * 401を一度だけ許容する。強制更新は FreeeTokenService 側でロック・読み直しを行うため、
     * ここで再試行してもリフレッシュトークンを二重消費することはない。
     */
    protected function send(FreeeCredential $credential, string $path, array $query, bool $retriedAfter401 = false): Response
    {
        $token = $this->tokens->accessToken($credential);

        try {
            return Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->get($this->baseUrl().$path, $query)
                ->throw();
        } catch (RequestException $exception) {
            $status = $exception->response?->status();

            if ($status === 401 && ! $retriedAfter401) {
                Log::info('freee API returned 401; forcing token refresh and retrying once.', [
                    'freee_credential_id' => $credential->id,
                    'path' => $path,
                ]);

                $this->tokens->refresh($credential, force: true);

                return $this->send($credential, $path, $query, retriedAfter401: true);
            }

            Log::warning('freee API request failed.', [
                'freee_credential_id' => $credential->id,
                'product' => $this->productLabel(),
                'path' => $path,
                'status' => $status,
            ]);

            throw ValidationException::withMessages([
                'message' => $this->errorMessage($exception, $status),
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('freee API connection failed.', [
                'freee_credential_id' => $credential->id,
                'product' => $this->productLabel(),
                'path' => $path,
            ]);

            throw ValidationException::withMessages([
                'message' => $this->productLabel().' API接続エラー：'.$this->clean($exception->getMessage()),
            ]);
        }
    }

    /**
     * ボディ付きリクエスト。401は一度だけ拾い直す。
     *
     * 副作用のある呼び出しなので、それ以外のリトライは一切しない
     * （タイムアウト後の再送は同じ部門を二重に作りかねない）。
     */
    protected function sendJson(
        FreeeCredential $credential,
        string $method,
        string $path,
        array $body,
        bool $retriedAfter401 = false,
    ): Response {
        $token = $this->tokens->accessToken($credential);

        try {
            return Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->{$method}($this->baseUrl().$path, $body)
                ->throw();
        } catch (RequestException $exception) {
            $status = $exception->response?->status();

            if ($status === 401 && ! $retriedAfter401) {
                $this->tokens->refresh($credential, force: true);

                return $this->sendJson($credential, $method, $path, $body, retriedAfter401: true);
            }

            Log::warning('freee API write failed.', [
                'freee_credential_id' => $credential->id,
                'product' => $this->productLabel(),
                'method' => $method,
                'path' => $path,
                'status' => $status,
            ]);

            throw ValidationException::withMessages([
                'message' => $this->errorMessage($exception, $status),
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('freee API write connection failed.', [
                'freee_credential_id' => $credential->id,
                'product' => $this->productLabel(),
                'method' => $method,
                'path' => $path,
            ]);

            throw ValidationException::withMessages([
                // 到達したかどうか分からないため、利用者には確認を促す。
                'message' => $this->productLabel().' API接続エラー：'.$this->clean($exception->getMessage())
                    .' 処理が反映されたかfreee側で確認してください。',
            ]);
        }
    }

    protected function errorMessage(RequestException $exception, ?int $status): string
    {
        if ($status === 429) {
            // freeeの制限は1事業所あたり300リクエスト/分。
            return 'freee APIの利用制限（300リクエスト/分）に達しました。時間をおいて再試行してください。';
        }

        $payload = $exception->response?->json();
        $payload = is_array($payload) ? $payload : [];

        $detail = $payload['message']
            ?? $payload['error_description']
            ?? $payload['error']
            ?? $exception->response?->body()
            ?? $exception->getMessage();

        if (is_array($detail) || is_object($detail)) {
            $detail = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // errors[].messages が入ってくる場合はそちらを優先して読ませる。
        if (isset($payload['errors']) && is_array($payload['errors'])) {
            $messages = collect($payload['errors'])
                ->flatMap(fn ($error) => (array) ($error['messages'] ?? []))
                ->filter()
                ->take(3)
                ->implode(' / ');

            if ($messages !== '') {
                $detail = $messages;
            }
        }

        return $this->productLabel().' APIエラー'.($status ? '（HTTP '.$status.'）' : '').'：'.$this->clean((string) $detail);
    }

    protected function clean(string $detail): string
    {
        $detail = preg_replace('/\s+/u', ' ', strip_tags($detail)) ?? $detail;

        return mb_substr(trim($detail), 0, 500);
    }
}
