<?php

namespace App\Support;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * ラベル項目のHTMLを、安全な範囲だけに削って保存する。
 *
 * ラベルは画面で v-html として出す。書けるのはアプリの管理権限を持つ人だけだが、出来上がりは
 * そのアプリを見る全員のブラウザで実行されるので、書き手を信用するかどうかとは別の話になる
 * （`<img onerror=…>` ひとつで、閲覧しただけの人のセッションが取られる）。
 *
 * 方針は「許したものだけ残す」。正規表現ではなくDOMとして読んでから組み直すので、
 * `<img src=x onerror=…>` のような属性も、`<scr<script>ipt>` のような入れ子の細工も残らない。
 * 許可リストは RichEditor（tiptap）が実際に出力するものに合わせてある。
 */
class FlowRichText
{
    /** 残すタグ => そのタグで残す属性。 */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'div' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'strike' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [], 'code' => [], 'pre' => [],
        'span' => ['style'],
        'mark' => ['style'],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt'],
    ];

    /** style で残す宣言。色と装飾だけ——位置指定やURL読み込みは通さない。 */
    private const ALLOWED_STYLES = [
        'color', 'background-color', 'font-weight', 'font-style',
        'text-decoration', 'text-decoration-line', 'text-align',
    ];

    /** 保存できる長さの上限（文字）。装飾で膨らむので素のテキストより広く取る。 */
    public const MAX_LENGTH = 20000;

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        if (mb_strlen($html) > self::MAX_LENGTH) {
            $html = mb_substr($html, 0, self::MAX_LENGTH);
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // 断片を既知の入れ物に包んでから読む。包まないと、charset指定用の meta が
        // documentElement になって本文がその外に出てしまい、掃除も出力も素通りする。
        // meta を付けないとUTF-8がバイト単位で解釈されて日本語が壊れる。
        $ok = $dom->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
            .'<div id="flow-richtext-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $ok ? (new DOMXPath($dom))->query('//div[@id="flow-richtext-root"]')->item(0) : null;
        if (! $root) {
            // 読めないHTMLは、タグを落として素のテキストとして残す（黙って消さない）
            return htmlspecialchars(strip_tags($html), ENT_QUOTES, 'UTF-8');
        }

        self::clean($root, $dom);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    /**
     * 表示用の素のテキスト（一覧やツールチップ、HTMLを出せない項目向け）。
     *
     * 見た目の区切りだけは残す：ブロックの終わりを改行に置き換えてからタグを外さないと、
     * 段落が繋がって一続きの文になってしまう。
     *
     * ワープロから貼られたHTMLは、中身の無い段落（`<p><span>&nbsp;</span></p>` など）を
     * 大量に挟んでくる。そのまま落とすと空白だけの行が延々と続くので、空行は1行にまとめる。
     */
    public static function toPlainText(?string $html): string
    {
        $text = preg_replace('#<\s*(br|/p|/div|/li|/h[1-6]|/tr)\s*/?\s*>#i', "\n", (string) $html) ?? (string) $html;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // ノーブレークスペースは見えないだけで普通の空白ではない。ここで揃えておかないと
        // 「空行のはずなのに空でない」行が残る。
        $text = str_replace(["\u{00a0}", "\u{3000}\n"], [' ', "\n"], $text);
        $text = preg_replace('/[ \t]+$/mu', '', $text) ?? $text;      // 行末の空白
        $text = preg_replace("/\n\s*\n(\s*\n)+/u", "\n\n", $text) ?? $text;   // 空行の連続は1行に

        return trim($text);
    }

    /** 許可されていない要素・属性を落としながら木を歩く。 */
    private static function clean(DOMNode $node, DOMDocument $dom): void
    {
        foreach (iterator_to_array($node->childNodes ?? []) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->nodeName);

                // script/style は中身ごと消す。他の未許可タグは中身を残して包みだけ外す
                // （文章が消えるほうが利用者にとっては大きな事故になる）。
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'svg', 'math'], true)) {
                    $child->parentNode?->removeChild($child);

                    continue;
                }

                // <font color=…> は今のエディタは書かないが、kintoneのラベルはこれを多用している
                // （取引先アプリのラベルだけで18か所）。許可リストから外すと色が全部落ちるので、
                // span + style に直してから通す——見た目が残るうえ、RichEditorでそのまま編集できる。
                if ($tag === 'font') {
                    $child = self::fontToSpan($child, $dom);
                    $tag = 'span';
                }

                self::clean($child, $dom);

                if (! array_key_exists($tag, self::ALLOWED)) {
                    self::unwrap($child);

                    continue;
                }

                self::cleanAttributes($child, $tag);

                // src を落とされた img は壊れた画像アイコンになるだけなので、要素ごと消す
                if ($tag === 'img' && ! $child->hasAttribute('src')) {
                    $child->parentNode?->removeChild($child);
                }

                continue;
            }

            // コメントは消す（条件付きコメントで実行される環境がある）
            if ($child->nodeType === XML_COMMENT_NODE) {
                $child->parentNode?->removeChild($child);
            }
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = self::ALLOWED[$tag];

        /** @var DOMAttr $attr */
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->nodeName);   // on* もここで全部落ちる

                continue;
            }

            $value = trim($attr->nodeValue ?? '');

            if ($name === 'href' || $name === 'src') {
                if (! self::safeUrl($value)) {
                    $el->removeAttribute($attr->nodeName);

                    continue;
                }
            }
            if ($name === 'style') {
                $safe = self::safeStyle($value);
                $safe === '' ? $el->removeAttribute('style') : $el->setAttribute('style', $safe);
            }
        }

        // 別タブで開くリンクは opener を渡さない
        if ($tag === 'a' && $el->hasAttribute('href')) {
            $el->setAttribute('target', '_blank');
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /** http(s) と、同じサイト内の相対パスだけ。javascript: や data: は通さない。 */
    private static function safeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }
        // 制御文字を挟んで "java\0script:" のように偽装されるのを潰してから判定する
        $probe = strtolower(preg_replace('/[\x00-\x20]/', '', $url) ?? $url);

        foreach (['javascript:', 'vbscript:', 'data:', 'file:'] as $bad) {
            if (str_starts_with($probe, $bad)) {
                return false;
            }
        }

        return (bool) preg_match('#^(https?://|/|\#|mailto:)#i', $probe);
    }

    /** 許可した宣言だけを残して style を組み直す。 */
    private static function safeStyle(string $style): string
    {
        $out = [];
        foreach (explode(';', $style) as $decl) {
            $parts = explode(':', $decl, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $prop = strtolower(trim($parts[0]));
            $value = trim($parts[1]);

            if (! in_array($prop, self::ALLOWED_STYLES, true) || $value === '') {
                continue;
            }
            // url() と expression() は色の指定に要らない。通す理由が無いので落とす。
            if (preg_match('/(url\s*\(|expression\s*\(|javascript:)/i', $value)) {
                continue;
            }
            if (! preg_match('/^[#a-zA-Z0-9%.,()\/\s-]+$/', $value)) {
                continue;
            }
            $out[] = $prop.': '.$value;
        }

        return implode('; ', $out);
    }

    /**
     * <font color="#f00" style="…"> を <span style="color: #f00; …"> に置き換える。
     * 中身と既存の style は引き継ぐ（あとで安全な宣言だけに絞られる）。
     */
    private static function fontToSpan(DOMElement $font, DOMDocument $dom): DOMElement
    {
        $span = $dom->createElement('span');

        $styles = [];
        if ($existing = trim($font->getAttribute('style'))) {
            $styles[] = rtrim($existing, ';');
        }
        if ($color = trim($font->getAttribute('color'))) {
            $styles[] = 'color: '.$color;
        }
        if ($styles !== []) {
            $span->setAttribute('style', implode('; ', $styles));
        }

        while ($font->firstChild) {
            $span->appendChild($font->firstChild);
        }
        $font->parentNode?->replaceChild($span, $font);

        return $span;
    }

    /** タグだけ外して中身を親に移す。 */
    private static function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;
        if (! $parent) {
            return;
        }
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }
}
