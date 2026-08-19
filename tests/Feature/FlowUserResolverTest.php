<?php

namespace Tests\Feature;

use App\Support\FlowUserResolver;
use Tests\TestCase;

/**
 * 氏名の文字列からユーザーを引き当てる規則。
 *
 * kintoneから移したデータは担当者を文字列で持っており、姓名の間の空白の有無だけが
 * こちらの users.name と違う。突き合わせの正規化と、誰にも結び付けない条件を固定する。
 */
class FlowUserResolverTest extends TestCase
{
    /** 突き合わせは空白を無視する。半角も全角も同じ。 */
    public function test_normalize_ignores_spaces_of_both_widths(): void
    {
        $this->assertSame('中野龍太郎', FlowUserResolver::normalize('中野 龍太郎'));
        $this->assertSame('中野龍太郎', FlowUserResolver::normalize('中野　龍太郎'));
        $this->assertSame('中野龍太郎', FlowUserResolver::normalize('  中野 龍太郎 '));
        $this->assertSame('中野龍太郎', FlowUserResolver::normalize('中野龍太郎'));
    }

    /** 空欄は空欄のまま（引き当てを試みない）。 */
    public function test_blank_input_resolves_to_nothing(): void
    {
        $r = new FlowUserResolver;

        $this->assertNull($r->resolve(null));
        $this->assertNull($r->resolve(''));
        $this->assertNull($r->resolve('   '));
        $this->assertSame([], $r->problems(), '空欄は問題として報告しない');
    }

    /** kintoneのユーザー選択（[{code,name}]）も氏名の文字列も、同じ入口で扱える。 */
    public function test_resolve_many_accepts_both_shapes_and_drops_misses(): void
    {
        $r = new FlowUserResolver;

        // 実在しない名前しか渡していないので、結果は空配列になる（例外は投げない）
        $this->assertSame([], $r->resolveMany('該当しない名前ABC'));
        $this->assertSame([], $r->resolveMany([['code' => 'nobody', 'name' => '該当しない名前ABC']]));
        $this->assertSame([], $r->resolveMany(null));
        $this->assertSame([], $r->resolveMany(''));

        $this->assertArrayHasKey('該当しない名前ABC', $r->problems());
    }

    /** 引けなかった理由は残す——黙って空にすると、移行の取りこぼしに気づけない。 */
    public function test_unknown_name_is_reported(): void
    {
        $r = new FlowUserResolver;
        $r->resolve('存在しない人名XYZ');

        $this->assertSame('該当するユーザーがいません', $r->problems()['存在しない人名XYZ'] ?? null);
    }
}
