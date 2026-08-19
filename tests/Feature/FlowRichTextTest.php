<?php

namespace Tests\Feature;

use App\Support\FlowRichText;
use Tests\TestCase;

/**
 * ラベルHTMLの無害化。
 *
 * ラベルは v-html で出すので、ここを抜けたものはそのまま閲覧者のブラウザで動く。書けるのは
 * アプリ管理者だけだが、影響を受けるのは「そのアプリを見ただけの人」なので、書き手を信用するか
 * どうかとは別に落とす必要がある。
 */
class FlowRichTextTest extends TestCase
{
    private function clean(string $html): string
    {
        return FlowRichText::sanitize($html);
    }

    /** RichEditor が実際に出す装飾はそのまま通す。 */
    public function test_keeps_the_formatting_the_editor_produces(): void
    {
        $out = $this->clean('<p><strong>太字</strong>と<em>斜体</em>と<u>下線</u>と<s>打消</s></p>');

        $this->assertStringContainsString('<strong>太字</strong>', $out);
        $this->assertStringContainsString('<em>斜体</em>', $out);
        $this->assertStringContainsString('<u>下線</u>', $out);
        $this->assertStringContainsString('<s>打消</s>', $out);
    }

    /** 日本語がバイト単位で壊れない（DOMDocument の既定はUTF-8ではない）。 */
    public function test_japanese_survives_intact(): void
    {
        $out = $this->clean('<p>個人事業主かつ口座が個人名義の場合はマイナンバーの提出が必要です。</p>');

        $this->assertStringContainsString('個人事業主かつ口座が個人名義の場合はマイナンバーの提出が必要です。', $out);
    }

    /** 色とマーカーは残す（注意書きの意味が色に乗っている）。 */
    public function test_keeps_allowed_style_declarations(): void
    {
        $out = $this->clean('<p><span style="color: #ff0000">赤字</span><mark style="background-color: #fcffa6">마커</mark></p>');

        $this->assertStringContainsString('color: #ff0000', $out);
        $this->assertStringContainsString('background-color: #fcffa6', $out);
    }

    public function test_strips_script_tags_and_their_contents(): void
    {
        $out = $this->clean('<p>前</p><script>alert(1)</script><p>後</p>');

        $this->assertStringNotContainsString('script', $out);
        $this->assertStringNotContainsString('alert', $out);
        $this->assertStringContainsString('前', $out);
        $this->assertStringContainsString('後', $out);
    }

    /** 一番ありがちな入口。タグを消すだけの実装はこれを通してしまう。 */
    public function test_strips_event_handler_attributes(): void
    {
        foreach ([
            '<img src="x" onerror="alert(1)">',
            '<p onclick="alert(1)">押して</p>',
            '<p ONMOUSEOVER="alert(1)">乗せて</p>',
            '<img src=x onerror=alert(1)>',
        ] as $payload) {
            $out = $this->clean($payload);
            $this->assertStringNotContainsString('alert', $out, "leaked: {$payload}");
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $out, "leaked handler: {$payload}");
        }
    }

    public function test_rejects_dangerous_url_schemes(): void
    {
        foreach ([
            '<a href="javascript:alert(1)">x</a>',
            '<a href="JaVaScRiPt:alert(1)">x</a>',
            "<a href=\"java\tscript:alert(1)\">x</a>",
            '<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>',
            '<img src="javascript:alert(1)">',
        ] as $payload) {
            $out = $this->clean($payload);
            $this->assertStringNotContainsString('javascript', strtolower($out), "leaked: {$payload}");
            $this->assertStringNotContainsString('data:text/html', strtolower($out), "leaked: {$payload}");
        }
    }

    public function test_keeps_ordinary_links_and_forces_safe_target(): void
    {
        $out = $this->clean('<a href="https://example.com/a">リンク</a>');

        $this->assertStringContainsString('href="https://example.com/a"', $out);
        $this->assertStringContainsString('rel="noopener noreferrer"', $out);
    }

    /** style は色まわりだけ。位置指定や url() は通さない。 */
    public function test_drops_unsafe_style_declarations(): void
    {
        $out = $this->clean('<span style="color:#0f0; position:fixed; top:0; background-image:url(javascript:alert(1))">x</span>');

        $this->assertStringContainsString('color: #0f0', $out);
        $this->assertStringNotContainsString('position', $out);
        $this->assertStringNotContainsString('url(', $out);
    }

    /** 許可外のタグは中身を残して包みだけ外す——文章そのものを消さない。 */
    public function test_unwraps_unknown_tags_but_keeps_their_text(): void
    {
        $out = $this->clean('<marquee>大事な注意書き</marquee>');

        $this->assertStringNotContainsString('marquee', $out);
        $this->assertStringContainsString('大事な注意書き', $out);
    }

    /**
     * 入れ子で細工されたタグも、DOMとして読んでいるので復活しない。
     *
     * 残骸の「alert(1)」という**文字**は残る（出力は 'ipt&gt;alert(1)ipt&gt;'）。これは画面に
     * そう表示されるだけで実行はされない。危ないのは要素が組み上がることなので、見るべきは
     * script要素が無いことと、山括弧がエスケープされて新しいタグを作れないこと。
     */
    public function test_nested_tag_smuggling_does_not_reassemble(): void
    {
        $out = $this->clean('<scr<script>ipt>alert(1)</scr</script>ipt>');

        $this->assertStringNotContainsString('<script', strtolower($out));
        $this->assertStringNotContainsString('<', $out, '生の山括弧が残るとタグを組み直せてしまう');
    }

    /** src を落とした img は残さない（壊れた画像アイコンになるだけ）。 */
    public function test_drops_images_left_without_a_source(): void
    {
        $this->assertStringNotContainsString('<img', $this->clean('<img src="javascript:alert(1)" onerror="alert(1)">'));
        $this->assertStringContainsString('<img', $this->clean('<img src="/lesson_files/a.png" alt="図">'));
    }

    /**
     * kintoneのラベルは <font color> を多用している（取引先アプリだけで18か所）。
     * 許可リストから外して包みを剥がすと色が全部消えるので、span+style に直して残す。
     */
    public function test_converts_legacy_font_tags_into_styled_spans(): void
    {
        $out = $this->clean('<font color="#ff0000" style="font-weight:bold"><u>マイナンバーの提出</u></font>');

        $this->assertStringNotContainsString('<font', $out);
        $this->assertStringContainsString('color: #ff0000', $out);
        $this->assertStringContainsString('font-weight: bold', $out);
        $this->assertStringContainsString('<u>マイナンバーの提出</u>', $out);
    }

    public function test_strips_iframes_and_forms(): void
    {
        $out = $this->clean('<iframe src="https://evil.test"></iframe><form action="/x"><input name="a"></form>');

        foreach (['iframe', '<form', '<input'] as $bad) {
            $this->assertStringNotContainsString($bad, strtolower($out));
        }
    }

    public function test_empty_and_plain_text_are_handled(): void
    {
        $this->assertSame('', FlowRichText::sanitize(null));
        $this->assertSame('', FlowRichText::sanitize('   '));
        $this->assertStringContainsString('ただの文', $this->clean('ただの文'));
    }

    /** HTMLを出せない場所（一覧・ツールチップ）向けの素のテキスト化。 */
    public function test_plain_text_conversion_keeps_line_structure(): void
    {
        $text = FlowRichText::toPlainText('<p>1行目</p><p>2行目<br>3行目</p>');

        $this->assertSame("1行目\n2行目\n3行目", $text);
        $this->assertStringNotContainsString('<', $text);
    }
}
