<?php

namespace Tests\Feature\Freee;

use App\Models\FreeeCredential;
use App\Services\Freee\FreeeAccountingClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 取引先一覧の並び替えとページング。
 *
 * freeeの会計APIには sort / order に相当するパラメータが存在せず、取引先は常に
 * id昇順（古い順）で返る。そのため全件を取得してキャッシュし、絞り込み・並び替え・
 * ページングをこちら側で行っている。その前提を固定する。
 */
class FreeeAccountingClientTest extends TestCase
{
    private FreeeAccountingClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('services.freee.company_id', null);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Cache::flush();

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

        $this->client = app(FreeeAccountingClient::class);
    }

    private function credential(): FreeeCredential
    {
        return FreeeCredential::query()->create([
            'label' => 'テスト事業所',
            'client_id' => 'client-abc',
            'client_secret' => 'secret-abc',
            'redirect_uri' => 'https://example.test/admin/freee/callback',
            'company_id' => 620580,
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'status' => FreeeCredential::STATUS_CONNECTED,
            'access_token_expires_at' => now()->addHours(5),
            'refresh_token_expires_at' => now()->addDays(90),
            'active' => true,
        ]);
    }

    /**
     * freeeと同じ「id昇順＝古い順」で返すフェイク。
     *
     * @return array<int, array<string, mixed>>
     */
    private function fakePartners(int $count): array
    {
        return collect(range(1, $count))
            ->map(fn (int $i) => [
                'id' => 3389897 + $i,
                'code' => str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'name' => '取引先'.$i,
                'name_kana' => 'トリヒキサキ'.$i,
                'update_date' => now()->subDays($count - $i)->toDateString(),
                'available' => true,
            ])
            ->all();
    }

    private function fake(int $count): void
    {
        Http::fake([
            '*/api/1/partners*' => Http::response(['partners' => $this->fakePartners($count)]),
        ]);
    }

    public function test_the_default_order_is_newest_first_even_though_freee_returns_oldest_first(): void
    {
        $this->fake(120);

        $result = $this->client->partners($this->credential(), page: 1);

        $this->assertSame('id', $result['sort']);
        $this->assertSame('desc', $result['direction']);
        // freeeは3389898から昇順で返す。先頭は最後に登録された取引先であるべき。
        $this->assertSame(3389897 + 120, $result['partners'][0]['id']);
        $this->assertSame(3389897 + 119, $result['partners'][1]['id']);
    }

    public function test_it_reports_a_real_total_and_last_page(): void
    {
        $this->fake(120);

        $result = $this->client->partners($this->credential(), page: 1);

        // 全件を持っているので、freeeが返さない総件数と最終ページを出せる。
        $this->assertSame(120, $result['total_count']);
        $this->assertSame(3, $result['last_page']);
        $this->assertCount(50, $result['partners']);
        $this->assertSame(1, $result['from']);
        $this->assertSame(50, $result['to']);
        $this->assertTrue($result['has_more']);
    }

    public function test_the_last_page_is_partial_and_has_no_next(): void
    {
        $this->fake(120);

        $result = $this->client->partners($this->credential(), page: 3);

        $this->assertCount(20, $result['partners']);
        $this->assertSame(101, $result['from']);
        $this->assertSame(120, $result['to']);
        $this->assertFalse($result['has_more']);
    }

    public function test_a_page_beyond_the_end_clamps_to_the_last_page(): void
    {
        $this->fake(120);

        $result = $this->client->partners($this->credential(), page: 99);

        $this->assertSame(3, $result['page']);
        $this->assertCount(20, $result['partners']);
    }

    public function test_ascending_order_returns_the_oldest_first(): void
    {
        $this->fake(60);

        $result = $this->client->partners($this->credential(), page: 1, sort: 'id', direction: 'asc');

        $this->assertSame(3389898, $result['partners'][0]['id']);
    }

    public function test_it_can_sort_by_name_and_update_date(): void
    {
        $this->fake(60);

        $byName = $this->client->partners($this->credential(), page: 1, sort: 'name', direction: 'asc');
        $names = array_column($byName['partners'], 'name');
        $sorted = $names;
        usort($sorted, 'strnatcasecmp');
        $this->assertSame($sorted, $names);

        $byDate = $this->client->partners($this->credential(), page: 1, sort: 'update_date', direction: 'desc');
        $dates = array_column($byDate['partners'], 'update_date');
        $this->assertSame($dates, collect($dates)->sortDesc()->values()->all());
    }

    public function test_an_unknown_sort_column_falls_back_to_id(): void
    {
        $this->fake(10);

        $result = $this->client->partners($this->credential(), page: 1, sort: 'phone; DROP TABLE');

        $this->assertSame('id', $result['sort']);
    }

    public function test_blank_values_sort_to_the_end_in_both_directions(): void
    {
        // カナ未入力の取引先が多いので、空欄が先頭に来ないことを保証する。
        Http::fake(['*/api/1/partners*' => Http::response(['partners' => [
            ['id' => 1, 'name' => 'あ', 'name_kana' => ''],
            ['id' => 2, 'name' => 'い', 'name_kana' => 'イロハ'],
            ['id' => 3, 'name' => 'う', 'name_kana' => null],
            ['id' => 4, 'name' => 'え', 'name_kana' => 'アイウ'],
        ]])]);

        foreach (['asc', 'desc'] as $direction) {
            $result = $this->client->partners($this->credential(), page: 1, sort: 'name_kana', direction: $direction);
            $kana = array_column($result['partners'], 'name_kana');

            $this->assertSame(
                ['', ''],
                array_map(fn ($v) => (string) $v, array_slice($kana, -2)),
                "空欄が末尾に来ていない（direction={$direction}）",
            );
        }
    }

    public function test_a_keyword_filters_across_code_name_and_kana_and_narrows_the_total(): void
    {
        $this->fake(120);

        $result = $this->client->partners($this->credential(), page: 1, keyword: '取引先11');

        // 取引先11, 110〜119 の11件。絞り込み後の総件数が正しいこと。
        $this->assertSame(11, $result['total_count']);
        $this->assertSame(1, $result['last_page']);
        foreach ($result['partners'] as $partner) {
            $this->assertStringContainsString('取引先11', $partner['name']);
        }
    }

    public function test_the_full_list_is_cached_so_paging_does_not_refetch(): void
    {
        $this->fake(120);
        $credential = $this->credential();

        $this->client->partners($credential, page: 1);
        $this->client->partners($credential, page: 2);
        $this->client->partners($credential, page: 3);

        // 1699件で約3秒かかるので、ページ送りで取り直してはいけない。
        Http::assertSentCount(1);
    }

    public function test_fresh_bypasses_the_cache(): void
    {
        $this->fake(120);
        $credential = $this->credential();

        $this->client->partners($credential, page: 1);
        $this->client->partners($credential, page: 1, fresh: true);

        Http::assertSentCount(2);
    }

    public function test_it_pages_through_freee_until_a_short_batch(): void
    {
        // limit上限3000。3000件ぴったり返ったら次のoffsetも取りに行く。
        Http::fakeSequence()
            ->push(['partners' => $this->fakePartners(3000)])
            ->push(['partners' => $this->fakePartners(10)]);

        $result = $this->client->partners($this->credential(), page: 1);

        $this->assertSame(3010, $result['total_count']);
        Http::assertSentCount(2);
    }

    public function test_the_configured_company_id_wins_over_the_credential(): void
    {
        Config::set('services.freee.company_id', '999999');
        $this->fake(1);

        $this->client->partners($this->credential(), page: 1);

        Http::assertSent(fn ($request) => $request['company_id'] == 999999);
    }

    public function test_it_hits_the_accounting_base_url_not_the_hr_one(): void
    {
        $this->fake(1);

        $this->client->partners($this->credential(), page: 1);

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://api.freee.co.jp/api/1/partners')
            && ! str_contains($request->url(), '/hr/'));
    }
}
