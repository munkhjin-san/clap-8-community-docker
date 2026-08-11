<?php

namespace App\Services\Freee;

use App\Models\FreeeCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * freee会計APIのクライアント。
 * 認証・401の拾い直し・エラー整形は FreeeBaseClient に持たせている。
 */
class FreeeAccountingClient extends FreeeBaseClient
{
    private const ACCOUNTING_BASE_URL = 'https://api.freee.co.jp';

    /** freeeのlimit上限。 */
    private const MAX_LIMIT = 3000;

    /** 全件取得の打ち切り。ここに達したらログを残す（黙って切り捨てない）。 */
    private const MAX_FETCH_PAGES = 20;

    /** 全件キャッシュの保持時間。取引先はマスタなので数分で十分。 */
    private const CACHE_TTL_SECONDS = 600;

    public const DEFAULT_PER_PAGE = 50;

    /** 部門名の上限（freee側の制約）。 */
    public const SECTION_NAME_MAX = 30;

    /** 取引先名の上限（freee会計の仕様）。 */
    public const PARTNER_NAME_MAX = 255;

    /**
     * 並び替えに使える項目。
     *
     * freeeの取引先APIには sort / order に相当するパラメータが存在しない
     * （会計APIのどのエンドポイントにも無い）。常にid昇順＝古い順で返るため、
     * 並び替えはこちらで全件を持ってから行う。
     */
    public const SORTABLE = ['id', 'code', 'name', 'name_kana', 'update_date'];

    protected function baseUrl(): string
    {
        return self::ACCOUNTING_BASE_URL;
    }

    protected function productLabel(): string
    {
        return 'freee会計';
    }

    /**
     * 取引先一覧。絞り込み・並び替え・ページングをすべてこちら側で行う。
     *
     * @return array{
     *     partners: array<int, array>, page: int, per_page: int, total_count: int,
     *     last_page: int, from: int, to: int, has_more: bool, sort: string, direction: string
     * }
     */
    public function partners(
        FreeeCredential $credential,
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?string $keyword = null,
        string $sort = 'id',
        string $direction = 'desc',
        bool $fresh = false,
    ): array {
        $perPage = max(1, min($perPage, 200));
        $sort = in_array($sort, self::SORTABLE, true) ? $sort : 'id';
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $rows = $this->cachedPartners($credential, $fresh);
        $rows = $this->filter($rows, $keyword);
        $rows = $this->sort($rows, $sort, $direction);

        $total = count($rows);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $slice = array_values(array_slice($rows, $offset, $perPage));

        return [
            'partners' => $slice,
            'page' => $page,
            'per_page' => $perPage,
            'total_count' => $total,
            'last_page' => $lastPage,
            'from' => $slice === [] ? 0 : $offset + 1,
            'to' => $offset + count($slice),
            'has_more' => $page < $lastPage,
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    /**
     * 部門（Section）一覧。ページングパラメータが無く、常に全件返る。
     *
     * @return array<int, array>
     */
    public function sections(FreeeCredential $credential, bool $fresh = false): array
    {
        $key = 'freee:sections:'.$this->companyId($credential);

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember(
            $key,
            self::CACHE_TTL_SECONDS,
            fn () => array_values($this->get($credential, '/api/1/sections')['sections'] ?? []),
        );
    }

    public function forgetSections(FreeeCredential $credential): void
    {
        Cache::forget('freee:sections:'.$this->companyId($credential));
    }

    /**
     * 単一部門の取得。連携済みプロジェクトの存在確認に使う。
     * 見つからない場合（削除済みなど）は null。
     */
    public function section(FreeeCredential $credential, int $sectionId): ?array
    {
        try {
            $payload = $this->get($credential, '/api/1/sections/'.$sectionId);
        } catch (ValidationException $exception) {
            if (str_contains((string) collect($exception->errors())->flatten()->first(), 'HTTP 404')) {
                return null;
            }

            throw $exception;
        }

        return $payload['section'] ?? null;
    }

    /**
     * 部門の新規作成。
     *
     * 重複を作らない責任は呼び出し側（FreeeSectionSyncService）にある。
     * ここでは名前の長さだけ freee の制約に合わせて面倒を見る。
     */
    public function createSection(FreeeCredential $credential, string $name): array
    {
        $payload = $this->post($credential, '/api/1/sections', [
            'company_id' => $this->companyId($credential),
            // 部門名は30文字以内。超える場合は正式名称に全文を残す。
            'name' => mb_substr($name, 0, self::SECTION_NAME_MAX),
            'long_name' => mb_substr($name, 0, 255),
        ]);

        // 作成したら一覧キャッシュは古い。
        $this->forgetSections($credential);

        return $payload['section'] ?? [];
    }

    /**
     * 全件をキャッシュ越しに取得する。1699件で約1MB・3秒程度なので、
     * ページ送りのたびに取り直さない。
     */
    /**
     * 取引先1件。無ければ null。
     *
     * 一覧キャッシュから引かずに直接取りに行く：こちらが持っている取引先IDが本当に生きているかを
     * 確かめるための呼び出しなので、10分前のキャッシュで「ある」と答えては意味がない。
     */
    public function partner(FreeeCredential $credential, int $partnerId): ?array
    {
        try {
            $payload = $this->get($credential, '/api/1/partners/'.$partnerId);
        } catch (ValidationException $exception) {
            if (str_contains((string) collect($exception->errors())->flatten()->first(), 'HTTP 404')) {
                return null;
            }

            throw $exception;
        }

        return $payload['partner'] ?? null;
    }

    /**
     * 取引先を作る。$attributes を渡さなければ名前だけを送る。
     */
    public function createPartner(FreeeCredential $credential, string $name, array $attributes = []): array
    {
        $payload = $this->post($credential, '/api/1/partners', $this->partnerPayload($credential, $name, $attributes));

        // 作ったら一覧キャッシュは古い。
        $this->forgetPartners($credential);

        return $payload['partner'] ?? [];
    }

    /**
     * 取引先を更新する。freeeのPUTは全項目置換なので、送らなかった項目は消えると考えて
     * 呼び出し側で必ず全項目を組み立てること。
     */
    public function updatePartner(FreeeCredential $credential, int $partnerId, string $name, array $attributes = []): array
    {
        $payload = $this->put(
            $credential,
            '/api/1/partners/'.$partnerId,
            $this->partnerPayload($credential, $name, $attributes),
        );

        $this->forgetPartners($credential);

        return $payload['partner'] ?? [];
    }

    /**
     * 取引先の書き込みボディ。
     *
     * `available` は送らない。freeeの取引先の作成・更新パラメータに含まれておらず、
     * 送ると弾かれる可能性がある。使用可否はfreee側で管理し、こちらは取り込むだけ。
     *
     * @param  array<string, mixed>  $attributes  住所は address_attributes にまとめて渡す
     */
    private function partnerPayload(FreeeCredential $credential, string $name, array $attributes): array
    {
        $body = array_merge($attributes, [
            'company_id' => $this->companyId($credential),
            'name' => mb_substr($name, 0, self::PARTNER_NAME_MAX),
        ]);

        // null は「変更しない」ではなく「空で送る」になり得るため落とす。
        // 空文字は利用者が意図的に消した場合なので残す。
        return array_filter($body, fn ($value) => $value !== null);
    }

    public function cachedPartners(FreeeCredential $credential, bool $fresh = false): array
    {
        $key = $this->partnersCacheKey($credential);

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, self::CACHE_TTL_SECONDS, fn () => $this->fetchAllPartners($credential));
    }

    public function forgetPartners(FreeeCredential $credential): void
    {
        Cache::forget($this->partnersCacheKey($credential));
    }

    private function partnersCacheKey(FreeeCredential $credential): string
    {
        return 'freee:partners:'.$this->companyId($credential);
    }

    /**
     * offsetを進めながら全件を取る。limit上限は3000。
     */
    private function fetchAllPartners(FreeeCredential $credential): array
    {
        $all = [];
        $offset = 0;

        for ($i = 0; $i < self::MAX_FETCH_PAGES; $i++) {
            $payload = $this->get($credential, '/api/1/partners', [
                'offset' => $offset,
                'limit' => self::MAX_LIMIT,
            ]);

            $batch = array_values($payload['partners'] ?? []);
            $all = array_merge($all, $batch);

            if (count($batch) < self::MAX_LIMIT) {
                return $all;
            }

            $offset += self::MAX_LIMIT;
        }

        // 打ち切った事実は必ず残す。件数が正しくないまま「全件」として扱わせない。
        Log::warning('freee partners fetch hit the page cap; the list may be incomplete.', [
            'freee_credential_id' => $credential->id,
            'fetched' => count($all),
            'max_pages' => self::MAX_FETCH_PAGES,
        ]);

        return $all;
    }

    /**
     * コード・名称・カナ・正式名称の部分一致。freeeのkeyword相当をこちらで行う
     * （総件数と並び順を絞り込み後も正しく保つため）。
     */
    private function filter(array $rows, ?string $keyword): array
    {
        if (! filled($keyword)) {
            return $rows;
        }

        $needle = mb_strtolower(trim($keyword));

        return array_values(array_filter($rows, function (array $row) use ($needle) {
            foreach (['code', 'name', 'name_kana', 'long_name', 'shortcut1', 'shortcut2'] as $field) {
                $value = $row[$field] ?? null;

                if (filled($value) && str_contains(mb_strtolower((string) $value), $needle)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * 空値は方向に関係なく末尾へ送る（カナ・コードが未入力の取引先が多いため）。
     */
    private function sort(array $rows, string $sort, string $direction): array
    {
        usort($rows, function (array $a, array $b) use ($sort, $direction) {
            $x = $a[$sort] ?? null;
            $y = $b[$sort] ?? null;

            $xEmpty = $x === null || $x === '';
            $yEmpty = $y === null || $y === '';

            if ($xEmpty || $yEmpty) {
                return $xEmpty <=> $yEmpty;
            }

            $comparison = is_numeric($x) && is_numeric($y)
                ? $x <=> $y
                : strnatcasecmp((string) $x, (string) $y);

            return $direction === 'asc' ? $comparison : -$comparison;
        });

        return $rows;
    }
}
