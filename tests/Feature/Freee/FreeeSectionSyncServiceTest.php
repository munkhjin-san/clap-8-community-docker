<?php

namespace Tests\Feature\Freee;

use App\Models\FreeeCredential;
use App\Models\ProjectRecord;
use App\Services\Freee\FreeeAccountingClient;
use App\Services\Freee\FreeeSectionSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * プロジェクト ⇄ freee部門の連携。
 *
 * 最重要の不変条件は「既存部門を重複させない」。freeeの部門APIには重複防止キーが無く、
 * 既存552件はすべて code が NULL なので code/upsert では既存に一致しない。
 * 実質のキーは name であり、名前で突き合わせてから作成する挙動を固定する。
 */
class FreeeSectionSyncServiceTest extends TestCase
{
    private FreeeSectionSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('services.freee.company_id', 620580);

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
            $table->string('company_name')->nullable();
            $table->string('external_cid')->nullable();
            $table->timestamps();
        });

        Schema::create('project_records', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->unsignedBigInteger('freee_section_id')->nullable();
            $table->timestamp('freee_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->service = app(FreeeSectionSyncService::class);
    }

    private function credential(): FreeeCredential
    {
        return FreeeCredential::query()->create([
            'label' => 'テスト事業所',
            'client_id' => 'client-abc',
            'client_secret' => 'secret-abc',
            'redirect_uri' => 'https://example.test/cb',
            'company_id' => 620580,
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'status' => FreeeCredential::STATUS_CONNECTED,
            'access_token_expires_at' => now()->addHours(5),
            'refresh_token_expires_at' => now()->addDays(90),
            'active' => true,
        ]);
    }

    private function project(string $name): ProjectRecord
    {
        return ProjectRecord::query()->create(['name' => $name]);
    }

    private function fakeSections(array $sections, ?array $createResponse = null): void
    {
        $fakes = [
            'api.freee.co.jp/api/1/sections?*' => Http::response(['sections' => $sections]),
            'api.freee.co.jp/api/1/sections' => Http::response(
                $createResponse ?? ['section' => ['id' => 999001, 'name' => '新規部門']]
            ),
        ];

        Http::fake($fakes);
    }

    public function test_an_existing_section_is_linked_without_writing_to_freee(): void
    {
        // 既にfreeeにある部門。作成せず紐付けるだけであるべき。
        $this->fakeSections([
            ['id' => 758205, 'name' => 'BS九州(AIﾏｲｸﾞﾚ)', 'code' => null],
            ['id' => 932679, 'name' => 'BS九州(ｽﾏ推)', 'code' => null],
        ]);

        $target = $this->project('BS九州(AIﾏｲｸﾞﾚ)');

        $result = $this->service->sync($this->credential(), $target);

        $this->assertSame(FreeeSectionSyncService::RESULT_LINKED, $result['result']);
        $this->assertSame(758205, $result['section_id']);
        $this->assertSame(758205, $target->fresh()->freee_section_id);

        // 部門一覧のGETのみで完結すること。POSTが飛べば重複作成を意味する。
        $posts = collect(Http::recorded())->filter(fn ($pair) => $pair[0]->method() === 'POST');
        $this->assertCount(0, $posts, 'POSTが飛んでいる（重複作成の危険）');
    }

    public function test_a_section_is_created_only_when_no_name_matches(): void
    {
        $this->fakeSections(
            [['id' => 1, 'name' => '(中野)新規部門', 'code' => null]],
            ['section' => ['id' => 777001, 'name' => '(石川)新規部門']],
        );

        $project = $this->project('(石川)新規部門');

        $result = $this->service->sync($this->credential(), $project);

        $this->assertSame(FreeeSectionSyncService::RESULT_CREATED, $result['result']);
        $this->assertSame(777001, $result['section_id']);
        $this->assertSame(777001, $project->fresh()->freee_section_id);

        $posts = collect(Http::recorded())->filter(fn ($pair) => $pair[0]->method() === 'POST');
        $this->assertCount(1, $posts);
        $body = $posts->first()[0]->data();
        $this->assertSame('(石川)新規部門', $body['name']);
        $this->assertSame(620580, $body['company_id']);
    }

    public function test_a_width_variant_twin_blocks_creation_instead_of_duplicating(): void
    {
        // freeeには既にこの手の重複が17組ある。さらに増やしてはいけない。
        $this->fakeSections([
            ['id' => 500, 'name' => 'BS九州（データ入力）', 'code' => null],
        ]);

        $project = $this->project('BS九州(ﾃﾞｰﾀ入力)');

        try {
            $this->service->sync($this->credential(), $project);
            $this->fail('表記ゆれの重複候補があるのに作成された');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('表記が似た部門', collect($exception->errors())->flatten()->first());
        }

        $this->assertNull($project->fresh()->freee_section_id);
        $posts = collect(Http::recorded())->filter(fn ($pair) => $pair[0]->method() === 'POST');
        $this->assertCount(0, $posts, '重複候補があるのにPOSTした');
    }

    public function test_the_section_list_is_always_refetched_before_deciding_to_create(): void
    {
        // 古いキャッシュで判断すると、その間に作られた部門を見落として重複する。
        $this->fakeSections(
            [['id' => 42, 'name' => '既存部門', 'code' => null]],
            ['section' => ['id' => 900, 'name' => '新しい案件']],
        );
        $credential = $this->credential();

        // 先にキャッシュを温める
        app(FreeeAccountingClient::class)->sections($credential);

        $this->service->sync($credential, $this->project('新しい案件'));

        $gets = collect(Http::recorded())
            ->filter(fn ($pair) => $pair[0]->method() === 'GET' && str_contains($pair[0]->url(), '/sections'));

        // 温めた1回 + sync時の取り直し1回
        $this->assertGreaterThanOrEqual(2, $gets->count(), 'キャッシュを信じて取り直していない');
    }

    public function test_a_section_already_linked_to_another_project_is_refused(): void
    {
        $this->fakeSections([['id' => 4242, 'name' => '共有部門', 'code' => null]]);

        $first = $this->project('共有部門');
        $first->update(['freee_section_id' => 4242]);

        $second = $this->project('共有部門');

        $this->expectException(ValidationException::class);

        try {
            $this->service->sync($this->credential(), $second);
        } finally {
            $this->assertNull($second->fresh()->freee_section_id);
        }
    }

    public function test_unlink_only_clears_our_column_and_never_calls_delete(): void
    {
        Http::fake();

        $project = $this->project('連携済み案件');
        $project->update(['freee_section_id' => 12345, 'freee_synced_at' => now()]);

        $this->service->unlink($project);

        $project->refresh();
        $this->assertNull($project->freee_section_id);
        $this->assertNull($project->freee_synced_at);
        $this->assertFalse($project->isFreeeSynced());

        // freee側の部門は消さない、というのが仕様。
        $deletes = collect(Http::recorded())->filter(fn ($pair) => $pair[0]->method() === 'DELETE');
        $this->assertCount(0, $deletes);
    }

    public function test_check_reports_a_section_that_no_longer_exists(): void
    {
        Http::fake([
            'api.freee.co.jp/api/1/sections/*' => Http::response(['message' => 'not found'], 404),
        ]);

        $project = $this->project('消えた案件');
        $project->update(['freee_section_id' => 555]);

        $result = $this->service->check($this->credential(), $project);

        $this->assertFalse($result['exists']);
        $this->assertStringContainsString('存在しません', $result['message']);
    }

    public function test_check_reports_a_name_mismatch(): void
    {
        Http::fake([
            'api.freee.co.jp/api/1/sections/*' => Http::response([
                'section' => ['id' => 555, 'name' => 'freee側で改名された部門'],
            ]),
        ]);

        $project = $this->project('当システムの名称');
        $project->update(['freee_section_id' => 555]);

        $result = $this->service->check($this->credential(), $project);

        $this->assertTrue($result['exists']);
        $this->assertFalse($result['name_matches']);
        $this->assertStringContainsString('名称が異なります', $result['message']);
        $this->assertNotNull($project->fresh()->freee_synced_at);
    }

    public function test_check_confirms_a_healthy_link(): void
    {
        Http::fake([
            'api.freee.co.jp/api/1/sections/*' => Http::response([
                'section' => ['id' => 555, 'name' => '一致している案件'],
            ]),
        ]);

        $project = $this->project('一致している案件');
        $project->update(['freee_section_id' => 555]);

        $result = $this->service->check($this->credential(), $project);

        $this->assertTrue($result['exists']);
        $this->assertTrue($result['name_matches']);
    }

    public function test_a_name_over_thirty_characters_is_refused_rather_than_silently_truncated(): void
    {
        $this->fakeSections([]);

        $long = str_repeat('あ', 31);
        $project = $this->project($long);

        try {
            $this->service->sync($this->credential(), $project);
            $this->fail('30文字超が通ってしまった');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('30文字', collect($exception->errors())->flatten()->first());
        }

        $posts = collect(Http::recorded())->filter(fn ($pair) => $pair[0]->method() === 'POST');
        $this->assertCount(0, $posts);
    }
}
