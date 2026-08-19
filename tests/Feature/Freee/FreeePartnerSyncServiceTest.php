<?php

namespace Tests\Feature\Freee;

use App\Models\FlowDefinition;
use App\Models\FlowField;
use App\Models\FlowRecord;
use App\Models\FreeeCredential;
use App\Services\Freee\FreeePartnerSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * 取引先レコードと freee会計の取引先の突き合わせ。
 *
 * 一番大事なのは「勝手に付け替えない」こと：取引先IDが入っているのにfreeeで見つからないとき、
 * 名前で探し直して別の相手に繋ぐと、請求や入金の紐付けが黙って移る。そこを固定する。
 */
class FreeePartnerSyncServiceTest extends TestCase
{
    private FreeePartnerSyncService $service;

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
        Schema::create('flow_definitions', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('flow_fields', function ($table) {
            $table->increments('id');
            $table->integer('flow_definition_id');
            $table->string('key');
            $table->string('label');
            $table->string('input_type');
            $table->text('validation')->nullable();
            $table->timestamps();
        });
        Schema::create('flow_records', function ($table) {
            $table->increments('id');
            $table->integer('flow_definition_id');
            $table->integer('record_number')->nullable();
            $table->timestamps();
        });
        Schema::create('flow_record_values', function ($table) {
            $table->increments('id');
            $table->integer('flow_record_id');
            $table->integer('flow_field_id');
            $table->text('value_text')->nullable();
            $table->decimal('value_numeric', 20, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->dateTime('value_datetime')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->text('value_json')->nullable();
            $table->timestamps();
        });

        // 偽装から漏れた通信は本物のfreeeに飛ぶ。落ちるならURL付きで落ちてほしい。
        Http::preventStrayRequests();

        $this->service = app(FreeePartnerSyncService::class);
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
            'access_token_expires_at' => now()->addHours(5),
            'refresh_token_expires_at' => now()->addDays(90),
            'status' => FreeeCredential::STATUS_CONNECTED,
            'active' => true,
        ]);
    }

    /** 会社名と取引先IDを持つ取引先レコードを1件作る。 */
    private function record(?string $name, ?int $partnerId): FlowRecord
    {
        $def = FlowDefinition::query()->create(['name' => '取引先']);
        $nameField = FlowField::query()->create([
            'flow_definition_id' => $def->id, 'key' => '会社名', 'label' => '通称1', 'input_type' => 'short',
        ]);
        $idField = FlowField::query()->create([
            'flow_definition_id' => $def->id, 'key' => '取引先ID', 'label' => '取引先ID', 'input_type' => 'number',
        ]);

        $record = FlowRecord::query()->create(['flow_definition_id' => $def->id, 'record_number' => 1]);
        // 複数行の insert は先頭行のキーだけを列として使う。両方の列を必ず並べること。
        DB::table('flow_record_values')->insert([
            ['flow_record_id' => $record->id, 'flow_field_id' => $nameField->id, 'value_text' => $name, 'value_numeric' => null],
            ['flow_record_id' => $record->id, 'flow_field_id' => $idField->id, 'value_text' => null, 'value_numeric' => $partnerId],
        ]);

        return $record->load('values')->setRelation('definition', $def->load('fields'));
    }

    private function sync(FlowRecord $record): array
    {
        return $this->service->sync($this->credential(), $record, '会社名', '取引先ID');
    }

    /** IDが入っていて、freeeにも在る → 確認しただけ。名前検索も作成も走らない。 */
    public function test_an_existing_id_is_only_verified(): void
    {
        Http::fake(['*/api/1/partners/555*' => Http::response(['partner' => ['id' => 555, 'name' => 'エレコム株式会社']])]);

        $result = $this->sync($this->record('エレコム株式会社', 555));

        $this->assertSame(FreeePartnerSyncService::RESULT_VERIFIED, $result['result']);
        $this->assertSame(555, $result['partner_id']);
        Http::assertSentCount(1);
    }

    /**
     * IDが入っているのにfreeeで見つからない → 止める。
     * ここで名前検索に落ちると、別の取引先へ黙って付け替わる。
     */
    public function test_an_id_that_does_not_exist_in_freee_is_refused_not_relinked(): void
    {
        Http::fake(['*/api/1/partners/555*' => Http::response(['message' => 'not found'], 404)]);

        $this->expectException(ValidationException::class);
        try {
            $this->sync($this->record('エレコム株式会社', 555));
        } catch (ValidationException $e) {
            $this->assertStringContainsString('555', collect($e->errors())->flatten()->first());
            // 一覧を引きに行っていない＝名前で探し直していない
            Http::assertNotSent(fn ($r) => str_contains($r->url(), '/api/1/partners?'));
            throw $e;
        }
    }

    /** IDが空で、名前が完全一致する取引先が1件 → そのIDを返す（作らない）。 */
    public function test_an_empty_id_links_to_an_exact_name_match(): void
    {
        Http::fake(['*/api/1/partners*' => Http::response(['partners' => [
            ['id' => 11, 'name' => '別の会社'],
            ['id' => 22, 'name' => 'エレコム株式会社'],
        ]])]);

        $result = $this->sync($this->record('エレコム株式会社', null));

        $this->assertSame(FreeePartnerSyncService::RESULT_LINKED, $result['result']);
        $this->assertSame(22, $result['partner_id']);
        Http::assertNotSent(fn ($r) => $r->method() === 'POST');
    }

    /** IDが空で、名前でも見つからない → 作る。送るのは名前だけ。 */
    public function test_an_unknown_name_creates_a_partner(): void
    {
        Http::fake([
            '*/api/1/partners*' => Http::sequence()
                ->push(['partners' => [['id' => 11, 'name' => '別の会社']]])      // 一覧
                ->push(['partner' => ['id' => 77, 'name' => '新しい会社']]),       // 作成
        ]);

        $result = $this->sync($this->record('新しい会社', null));

        $this->assertSame(FreeePartnerSyncService::RESULT_CREATED, $result['result']);
        $this->assertSame(77, $result['partner_id']);
        Http::assertSent(function ($request) {
            if ($request->method() !== 'POST') {
                return false;
            }
            $this->assertSame('新しい会社', $request->data()['name']);
            $this->assertArrayNotHasKey('long_name', $request->data(), '送るのは名前だけ');

            return true;
        });
    }

    /** 同名が複数 → どれか選べないので止める。 */
    public function test_several_exact_matches_are_refused(): void
    {
        Http::fake(['*/api/1/partners*' => Http::response(['partners' => [
            ['id' => 11, 'name' => 'エレコム株式会社'],
            ['id' => 12, 'name' => 'エレコム株式会社'],
        ]])]);

        $this->expectException(ValidationException::class);
        $this->sync($this->record('エレコム株式会社', null));
    }

    /** 空白や大小だけが違う候補があるときは作らない（似た取引先が2つ並ぶのを防ぐ）。 */
    public function test_a_near_duplicate_blocks_creation(): void
    {
        Http::fake(['*/api/1/partners*' => Http::response(['partners' => [
            ['id' => 33, 'name' => 'エレコム 株式会社'],
        ]])]);

        $this->expectException(ValidationException::class);
        try {
            $this->sync($this->record('エレコム株式会社', null));
        } catch (ValidationException $e) {
            $this->assertStringContainsString('似た名前', collect($e->errors())->flatten()->first());
            Http::assertNotSent(fn ($r) => $r->method() === 'POST');
            throw $e;
        }
    }

    /** IDも名前も無い → 何もしない。 */
    public function test_a_record_with_neither_id_nor_name_is_refused(): void
    {
        Http::fake();

        $this->expectException(ValidationException::class);
        $this->sync($this->record('   ', null));
    }
}
