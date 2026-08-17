<?php

namespace App\Services;

use App\Models\FlowDefinition;
use App\Models\FlowRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Renders a "PDF tool" template (visual A4 canvas, free positioning) for a single record.
 *
 * Layout model (reconciles free-canvas with multi-page):
 *   - Each element carries `page` (1-based; absent = 1). Pages are emitted in order.
 *   - Within a page: elements above the 明細 table → written at exact page coordinates.
 *   - 明細 table                     → flowing <table> with a repeating <thead>; paginates.
 *   - Elements at/below the table    → written at page coordinates right after the table ends,
 *                                      keeping their relative layout to each other.
 *   - Running page footer            → page number, repeated on every page.
 *
 * A designed page can still spill onto several physical pages (that is what the flowing 明細 table
 * does). The next designed page simply starts after wherever the previous one ended, so the two
 * kinds of pagination compose instead of fighting: `paper.pages` is "how many pages the designer
 * laid out", never "how many pages the PDF has".
 *
 * Templates made before pages existed carry no `page` anywhere — they read as a single page 1,
 * which is byte-for-byte the old behaviour.
 *
 * A template may also carry a `background` PDF (an existing form — 契約書のひな形 etc.). Its page N
 * is stamped underneath designed page N, scaled to fill the sheet, and the elements are written on
 * top. mPDF 8 imports PDFs itself (FPDI ships with it), so this is the real vector page, not a
 * picture of it.
 *
 * IMPORTANT: mPDF only honours CSS absolute positioning on top-level elements, so fixed
 * elements are each written individually via WriteFixedPosHTML (page-relative mm coords)
 * instead of nested `position:absolute` divs (which mPDF silently renders in-flow).
 *
 * The designer canvas is A4 at 96dpi (794 x 1123 px); coordinates are stored in those px
 * and converted to mm here so the PDF matches the on-screen layout.
 */
class PdfRenderService
{
    private const PX2MM = 25.4 / 96;   // 1 css px at 96dpi

    private const CANVAS_W = 794;      // A4 portrait width in px @96dpi

    private const CANVAS_H = 1123;

    public function __construct(private FlowService $flow) {}

    /**
     * Base mPDF config, with Noto Sans JP registered as the default font.
     *
     * Without a Japanese font mPDF falls back to `sun-exta` (see FontVariables::backupSubsFont),
     * which is a CHINESE face — kanji came out in Simplified-Chinese shapes (直/骨/令/産 etc.) and
     * in a serif style that matched nothing else in the product. Noto Sans JP is what the web UI
     * uses, so PDFs now look like the app.
     *
     * The TTFs are static instances cut from the upstream variable font (mPDF cannot embed the
     * OTF/CFF builds of Noto — it needs TrueType glyf outlines). mPDF subsets on output, so a
     * 5.5 MB face still yields ~18 KB pages; the one-off font cache lands in tempDir (~80 ms cold,
     * ~15 ms warm).
     *
     * autoScriptToLang/autoLangToFont stay OFF: their only job here was to pick a CJK fallback,
     * and leaving them on lets mPDF swap our font back out for sun-exta on some runs.
     */
    private function mpdfConfig(): array
    {
        return [
            'mode' => 'utf-8',
            'tempDir' => storage_path('app/mpdf'),
            'fontDir' => array_merge(
                (new ConfigVariables())->getDefaults()['fontDir'],
                [resource_path('fonts')],
            ),
            'fontdata' => (new FontVariables())->getDefaults()['fontdata'] + [
                'notosansjp' => [
                    'R' => 'NotoSansJP-Regular.ttf',
                    'B' => 'NotoSansJP-Bold.ttf',
                ],
            ],
            'default_font' => 'notosansjp',
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
        ];
    }

    public function render(FlowDefinition $definition, FlowRecord $record, array $template): Mpdf
    {
        $fields = $definition->relationLoaded('fields') ? $definition->fields : $definition->fields()->get();
        $valuesById = $this->flow->recordValues($record, $fields);

        // field key => ['field' => model, 'value' => resolved]
        $byKey = [];
        foreach ($fields as $f) {
            $byKey[$f->key] = ['field' => $f, 'value' => $valuesById[(string) $f->id] ?? null];
        }

        $landscape = ($template['paper']['orientation'] ?? 'portrait') === 'landscape';
        // array_merge (not +) so per-render keys win over the base config
        $mpdf = new Mpdf(array_merge($this->mpdfConfig(), [
            'format' => $landscape ? 'A4-L' : 'A4',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 12,
            'margin_footer' => 5,
        ]));
        $mpdf->SetHTMLFooter('<div style="text-align:center; font-size:8pt; color:#9aa1ac;">{PAGENO} / {nbpg}</div>');

        $pages = $this->pagesOf($template);
        $background = $this->openBackground($mpdf, $template);

        // make sure page 1 exists before any fixed-position write
        $mpdf->WriteHTML($this->css().'<div></div>');

        $first = true;
        foreach ($pages as $i => $elements) {
            if (! $first) {
                $mpdf->AddPage();
            }
            $first = false;
            $this->stampBackground($mpdf, $background, $i + 1);
            $this->renderPage($mpdf, $elements, $byKey);
        }

        return $mpdf;
    }

    /**
     * 下敷きPDFが何ページあるか。読めないPDFは null（受け取る前に確かめるために公開している）。
     *
     * FPDIの無償パーサはPDF 1.5以降の一部（オブジェクトストリーム圧縮）を読めない。
     * 手元の実ファイル72件では71件が通り、落ちたのは1件だけだった。
     */
    public function probeBackground(string $absolutePath): ?int
    {
        if (! is_file($absolutePath)) {
            return null;
        }
        try {
            $probe = new Mpdf(['tempDir' => storage_path('app/mpdf')]);
            $count = (int) $probe->SetSourceFile($absolutePath);

            return $count > 0 ? $count : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 下敷きを開く。開けなければ黙って下敷き無しで出す——差込内容の方が本体なので、
     * ひな形が読めないという理由で帳票そのものを出せなくしない。
     *
     * @return array{pages: int}|null
     */
    private function openBackground(Mpdf $mpdf, array $template): ?array
    {
        $path = (string) ($template['background']['path'] ?? '');
        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            return null;
        }
        try {
            $pages = (int) $mpdf->SetSourceFile(Storage::disk('local')->path($path));

            return $pages > 0 ? ['pages' => $pages] : null;
        } catch (\Throwable $e) {
            Log::warning('PDF帳票: 下敷きを読めませんでした。下敷き無しで出力します。', [
                'path' => $path, 'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 今のページに下敷きを敷く。**要素より先**に置くこと（後だと差込内容を覆ってしまう）。
     *
     * 用紙いっぱいに合わせる。ひな形がA4でない（レターサイズ等）ときに、そのままの寸法で
     * 置くと画面のデザイナーとずれるため、位置合わせは画面側の見え方に揃える。
     *
     * @param  array{pages: int}|null  $background
     */
    private function stampBackground(Mpdf $mpdf, ?array $background, int $page): void
    {
        if ($background === null || $page > $background['pages']) {
            return;
        }
        try {
            $mpdf->UseTemplate($mpdf->ImportPage($page), 0, 0, $mpdf->w, $mpdf->h);
        } catch (\Throwable $e) {
            Log::warning('PDF帳票: 下敷きの'.$page.'ページ目を敷けませんでした。', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 要素をページごとに分ける。
     *
     * 空のページも残す：2ページ目を空にして3ページ目に置いた、という並びを勝手に詰めない。
     * ページ数は paper.pages と「実際に要素が置かれている最大ページ」の大きい方——設定だけを
     * 信じると、ページを減らした時に要素が黙って消えることになる。
     *
     * @return array<int, array<int, array>> 1ページ目から順に並んだ要素の配列
     */
    private function pagesOf(array $template): array
    {
        $elements = array_values($template['elements'] ?? []);

        $count = max(1, (int) ($template['paper']['pages'] ?? 1));
        foreach ($elements as $el) {
            $count = max($count, (int) ($el['page'] ?? 1));
        }

        $pages = array_fill(1, $count, []);
        foreach ($elements as $el) {
            $p = max(1, (int) ($el['page'] ?? 1));
            $pages[$p][] = $el;
        }

        return array_values($pages);
    }

    /**
     * 1ページ分。明細テーブルはページごとに1つだけ見る（複数置かれていても最初の1つ）。
     *
     * @param  array<int, array>  $elements
     */
    private function renderPage(Mpdf $mpdf, array $elements, array $byKey): void
    {
        $table = null;
        foreach ($elements as $el) {
            if (($el['type'] ?? '') === 'table') {
                $table = $el;
                break;
            }
        }
        $tableTop = $table ? (float) ($table['y'] ?? 0) : PHP_INT_MAX;

        $header = [];
        $after = [];
        foreach ($elements as $el) {
            if (($el['type'] ?? '') === 'table') {
                continue;
            }
            if ((float) ($el['y'] ?? 0) < $tableTop) {
                $header[] = $el;
            } else {
                $after[] = $el;
            }
        }

        // ---- header zone: exact page coordinates ----
        foreach ($header as $el) {
            $this->writeFixedElement($mpdf, $el, $byKey, $this->mm((float) ($el['y'] ?? 0)));
        }

        if (! $table) {
            return;
        }

        // ---- 明細 table: flows below the header zone, paginates with repeating thead ----
        $mpdf->WriteHTML(
            $this->css()
            .'<div style="height:'.$this->mm($tableTop).'mm;"></div>'
            .$this->renderTable($table, $byKey)
        );

        // ---- after-table zone: keeps relative layout, placed after wherever the table ended ----
        if (! $after) {
            return;
        }
        $minY = min(array_map(fn ($e) => (float) ($e['y'] ?? 0), $after));
        $maxBottom = max(array_map(fn ($e) => (float) ($e['y'] ?? 0) + (float) ($e['h'] ?? 0), $after));
        $bandH = $this->mm($maxBottom - $minY);
        $base = (float) $mpdf->y + 4; // mm — just below the table + totals
        $usableBottom = (float) $mpdf->h - 14;
        if ($base + $bandH > $usableBottom) { // the whole zone moves to a fresh page
            $mpdf->AddPage();
            $base = 10;
        }
        foreach ($after as $el) {
            $y = $base + $this->mm((float) ($el['y'] ?? 0) - $minY);
            $this->writeFixedElement($mpdf, $el, $byKey, $y);
        }
    }

    /* ---------------- fixed-position elements ---------------- */

    private function writeFixedElement(Mpdf $mpdf, array $el, array $byKey, float $yMm): void
    {
        $x = $this->mm((float) ($el['x'] ?? 0));
        $w = max(2, $this->mm((float) ($el['w'] ?? 100)));
        $h = max(2, $this->mm((float) ($el['h'] ?? 20)));
        // NB: no <style> block here — WriteFixedPosHTML renders it as literal text; inline styles only.
        $mpdf->WriteFixedPosHTML($this->elementHtml($el, $byKey, $w, $h), $x, $yMm, $w, $h);
    }

    /** Inner HTML of an element (no positioning — WriteFixedPosHTML provides the box). */
    private function elementHtml(array $el, array $byKey, float $wMm, float $hMm): string
    {
        $style = $el['style'] ?? [];
        switch ($el['type'] ?? 'text') {
            case 'text':
                return '<div style="'.$this->textCss($style).'">'.nl2br(e((string) ($el['text'] ?? ''))).'</div>';
            case 'field':
                // 改行は <br> に直す。HTMLは生の改行を空白として畳むので、複数行項目が
                // 「text breakline test」と1行に潰れて出ていた（静的テキスト側は元から nl2br 済み）。
                return '<div style="'.$this->textCss($style).'">'.nl2br(e($this->formatValue($byKey[$el['fieldKey'] ?? ''] ?? null, $el))).'</div>';
            case 'today': // 現在日付 — rendered at generation time
                $pattern = is_array($el['format'] ?? null) ? ($el['format']['pattern'] ?? 'Y年n月j日') : 'Y年n月j日';

                return '<div style="'.$this->textCss($style).'">'.e(Carbon::now()->format($pattern)).'</div>';
            case 'image':
                $src = $el['src'] ?? ($this->fileFieldImage($byKey[$el['fieldKey'] ?? ''] ?? null));
                // Only inline data URIs and same-origin uploads — never let a template make the
                // server fetch an arbitrary (internal) URL (SSRF). See isSafeImageSrc().
                if (! $src || ! $this->isSafeImageSrc($src)) {
                    return '<div></div>';
                }
                // mPDF has no object-fit — compute a "contain" fit ourselves so the image
                // keeps its aspect ratio inside the element box (horizontally centred).
                $dims = $this->imagePixelSize($src);
                if (($el['fit'] ?? 'contain') !== 'cover' && $dims) {
                    [$iw, $ih] = $dims;
                    $scale = min($wMm / $iw, $hMm / $ih);
                    $w = round($iw * $scale, 2);
                    $h = round($ih * $scale, 2);

                    return '<div style="text-align:center;"><img src="'.e($src).'" style="width:'.$w.'mm; height:'.$h.'mm;"></div>';
                }

                // unknown size (or cover) → let mPDF bound it proportionally
                return '<div style="text-align:center;"><img src="'.e($src).'" style="max-width:'.$wMm.'mm; max-height:'.$hMm.'mm;"></div>';
            case 'box':
                $bw = (int) ($style['borderWidth'] ?? 1);
                $bc = $this->safeColor($style['borderColor'] ?? '#333', '#333');
                $fill = $this->safeColor($style['fill'] ?? 'transparent', 'transparent');
                $radius = (int) ($style['radius'] ?? 0);

                return '<div style="height:'.$hMm.'mm; border:'.$bw.'px solid '.$bc.'; background:'.$fill.'; border-radius:'.$radius.'px;"></div>';
            case 'line':
                $bw = (int) ($style['borderWidth'] ?? 1);
                $bc = $this->safeColor($style['borderColor'] ?? '#333', '#333');

                return '<div style="height:0; border-top:'.$bw.'px solid '.$bc.';"></div>';
            default:
                return '<div></div>';
        }
    }

    /* Template style values land in inline CSS strings, so sanitize them (whitelist keywords,
       validate colors/numbers) rather than trusting the stored template verbatim. */
    private function safeAlign(mixed $a): string
    {
        return in_array($a, ['left', 'center', 'right'], true) ? $a : 'left';
    }

    private function safeColor(mixed $c, string $default): string
    {
        $c = (string) $c;

        return preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\([0-9.,%\s]+\)|[a-zA-Z]{1,20})$/', $c) ? $c : $default;
    }

    private function safeNum(mixed $n, float $default): float
    {
        return is_numeric($n) ? (float) $n : $default;
    }

    private function textCss(array $style): string
    {
        $pt = round($this->safeNum($style['fontSize'] ?? 12, 12) * 0.75, 1); // px -> pt
        $align = $this->safeAlign($style['align'] ?? 'left');
        $color = $this->safeColor($style['color'] ?? '#111', '#111');
        $weight = ! empty($style['bold']) ? '700' : '400';
        $lh = $this->safeNum($style['lineHeight'] ?? 1.4, 1.4);
        $css = "font-size:{$pt}pt; text-align:{$align}; color:{$color}; font-weight:{$weight}; line-height:{$lh};";
        if (! empty($style['italic'])) {
            $css .= 'font-style:italic;';
        }
        if (! empty($style['underline'])) {
            $css .= 'text-decoration:underline;';
        }

        return $css;
    }

    /* ---------------- 明細 table ---------------- */

    private function renderTable(array $table, array $byKey): string
    {
        $x = $this->mm((float) ($table['x'] ?? 0));
        $w = $this->mm((float) ($table['w'] ?? self::CANVAS_W));
        $srcField = $byKey[$table['sourceFieldKey'] ?? '']['field'] ?? null;
        $srcCols = is_array($srcField?->validation['columns'] ?? null) ? $srcField->validation['columns'] : [];

        $rows = $byKey[$table['sourceFieldKey'] ?? '']['value'] ?? [];
        if (! is_array($rows)) {
            $rows = [];
        }

        // columns not configured in the designer → derive from the source table field,
        // and as a last resort from the row data keys (legacy rows whose field lost its columns)
        $columns = $table['columns'] ?? [];
        if (! $columns && $srcCols) {
            $columns = array_map(fn ($c) => [
                'colKey' => $c['key'] ?? '',
                'label' => $c['label'] ?? ($c['key'] ?? ''),
                'align' => in_array($c['input_type'] ?? '', ['number', 'formula', 'calc'], true) ? 'right' : 'left',
            ], $srcCols);
        }
        if (! $columns && $rows) {
            $keys = [];
            foreach ($rows as $row) {
                if (is_array($row)) {
                    foreach (array_keys($row) as $k) {
                        $keys[$k] = true;
                    }
                }
            }
            $columns = array_map(fn ($k) => ['colKey' => $k, 'label' => $k], array_keys($keys));
        }

        // totals need an amount column — fall back to the first numeric source column,
        // then to the first numeric-valued row key
        $amountKey = $table['amountColKey'] ?? null;
        if (! $amountKey) {
            foreach ($srcCols as $c) {
                if (in_array($c['input_type'] ?? '', ['number', 'formula', 'calc'], true)) {
                    $amountKey = $c['key'] ?? null;
                    break;
                }
            }
        }
        if (! $amountKey && $rows) {
            foreach ($columns as $c) {
                $k = $c['colKey'] ?? '';
                if ($k !== '' && collect($rows)->contains(fn ($r) => is_array($r) && is_numeric($r[$k] ?? null))) {
                    $amountKey = $k;
                    break;
                }
            }
        }

        $headStyle = $table['headStyle'] ?? [];
        $headBg = $this->safeColor($headStyle['fill'] ?? '#f0f2f5', '#f0f2f5');
        $borderColor = $this->safeColor($table['borderColor'] ?? '#c9cfd8', '#c9cfd8');
        $cellPt = round($this->safeNum($table['fontSize'] ?? 11, 11) * 0.75, 1);
        $showHeader = ($table['showHeader'] ?? true) !== false;
        $showBorder = ($table['showBorder'] ?? true) !== false;
        $cellBorder = $showBorder ? "1px solid {$borderColor}" : 'none';

        $wrap = "margin-left:{$x}mm; width:{$w}mm;";
        $html = "<div style=\"{$wrap}\">";
        $html .= "<table class=\"detail\" style=\"width:100%; border-collapse:collapse; font-size:{$cellPt}pt;\">";

        // header row (repeats on page break) — omitted entirely when showHeader is off
        if ($showHeader) {
            $html .= '<thead><tr>';
            foreach ($columns as $c) {
                $cw = isset($c['width']) && $c['width'] ? 'width:'.(float) $c['width'].'%;' : '';
                $al = $this->safeAlign($c['align'] ?? 'left');
                $html .= "<th style=\"{$cw} text-align:{$al}; background:{$headBg}; border:{$cellBorder}; padding:4px 6px; font-weight:700;\">".e((string) ($c['label'] ?? '')).'</th>';
            }
            $html .= '</tr></thead>';
        }
        $html .= '<tbody>';

        // body rows
        $subtotal = 0.0;
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columns as $c) {
                $al = $this->safeAlign($c['align'] ?? 'left');
                $raw = is_array($row) ? ($row[$c['colKey'] ?? ''] ?? null) : null;
                // 明細の升でも同じ：複数行の値が1行に潰れないようにする
                $txt = nl2br(e($this->formatScalar($raw, $c['format'] ?? null, $c)));
                $html .= "<td style=\"text-align:{$al}; border:{$cellBorder}; padding:4px 6px;\">{$txt}</td>";
            }
            $html .= '</tr>';
            if ($amountKey !== null && is_array($row) && is_numeric($row[$amountKey] ?? null)) {
                $subtotal += (float) $row[$amountKey];
            }
        }
        $html .= '</tbody></table>';

        // totals
        if (! empty($table['showSubtotal']) || ! empty($table['showTax']) || ! empty($table['showTotal'])) {
            $rate = (float) (($table['tax']['rate'] ?? null) ?? 0.10);
            $taxAmt = round($subtotal * $rate);
            $total = $subtotal + $taxAmt;
            $cur = $table['currency'] ?? '¥';
            $rows2 = [];
            if (! empty($table['showSubtotal'])) {
                $rows2[] = ['小計', $subtotal];
            }
            if (! empty($table['showTax'])) {
                $rows2[] = ['消費税（'.rtrim(rtrim(number_format($rate * 100, 1), '0'), '.').'%）', $taxAmt];
            }
            if (! empty($table['showTotal'])) {
                $rows2[] = ['合計', $total];
            }
            $html .= '<table style="margin-top:3mm; margin-left:auto; border-collapse:collapse; font-size:'.$cellPt.'pt;">';
            foreach ($rows2 as $r) {
                $isTotal = ($r[0] === '合計');
                $lblStyle = 'padding:4px 12px; border:1px solid '.$borderColor.'; background:'.($isTotal ? '#eef1f6' : '#f7f8fa').'; font-weight:'.($isTotal ? '700' : '400').';';
                $valStyle = 'padding:4px 14px; border:1px solid '.$borderColor.'; text-align:right; font-weight:'.($isTotal ? '700' : '400').'; min-width:28mm;';
                $html .= "<tr><td style=\"{$lblStyle}\">".e($r[0])."</td><td style=\"{$valStyle}\">".e($cur.number_format($r[1])).'</td></tr>';
            }
            $html .= '</table>';
        }

        $html .= '</div>';

        return $html;
    }

    /* ---------------- value formatting ---------------- */

    private function formatValue(?array $bound, array $el): string
    {
        if (! $bound) {
            return (string) ($el['fallback'] ?? '');
        }
        $value = $bound['value'] ?? null;
        $field = $bound['field'] ?? null;
        $fmt = $el['format'] ?? null;
        $prefix = $el['prefix'] ?? '';
        $suffix = $el['suffix'] ?? '';
        $type = $field?->input_type;

        $s = $this->formatScalar($value, $fmt, ['input_type' => $type]);
        if ($s === '' && isset($el['fallback'])) {
            return (string) $el['fallback'];
        }

        return $prefix.$s.$suffix;
    }

    private function formatScalar($value, $fmt, $ctx = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value)) {
            if (isset($value['label'])) {
                return (string) $value['label'];
            } // reference/lookup cells
            $flat = array_map(
                fn ($v) => is_array($v) ? (string) ($v['label'] ?? json_encode($v, JSON_UNESCAPED_UNICODE)) : (string) $v,
                $value,
            );

            return implode(' / ', $flat);
        }
        if (is_bool($value)) {
            return $value ? '✓' : '';
        }

        $kind = is_array($fmt) ? ($fmt['kind'] ?? null) : $fmt;
        $type = is_array($ctx) ? ($ctx['input_type'] ?? null) : null;

        if ($kind === 'number' || (! $kind && $type === 'number' && is_numeric($value))) {
            $dec = is_array($fmt) ? (int) ($fmt['decimals'] ?? 0) : 0;

            return number_format((float) $value, $dec);
        }
        if ($kind === 'date' || (! $kind && in_array($type, ['date', 'datetime'], true))) {
            try {
                $pattern = is_array($fmt) ? ($fmt['pattern'] ?? 'Y年n月j日') : 'Y年n月j日';

                return Carbon::parse($value)->format($pattern);
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        return (string) $value;
    }

    private function fileFieldImage(?array $bound): ?string
    {
        $v = $bound['value'] ?? null;
        if (is_array($v) && isset($v[0]['url'])) {
            return $v[0]['url'];
        }

        return null;
    }

    /**
     * Image sources we allow into the PDF: inline data URIs (produced by the designer's upload)
     * and same-origin uploaded files (/cdn/…). Remote/other schemes are rejected so a template
     * can't drive a server-side fetch of an internal URL (SSRF).
     */
    private function isSafeImageSrc(string $src): bool
    {
        if (str_starts_with($src, 'data:image/')) {
            return true;
        }

        return str_starts_with($src, '/cdn/') && ! str_contains($src, '..');
    }

    /** Natural [width, height] in px of a data-URI image; null when unmeasurable. */
    private function imagePixelSize(string $src): ?array
    {
        // Only measure inline data URIs — never call getimagesize() on a path/URL, which would
        // fetch it (SSRF). Non-data images just fall back to proportional max-width bounding.
        if (! str_starts_with($src, 'data:')) {
            return null;
        }
        try {
            $comma = strpos($src, ',');
            if ($comma === false) {
                return null;
            }
            $bin = base64_decode(substr($src, $comma + 1), true);
            if ($bin === false) {
                return null;
            }
            $info = getimagesizefromstring($bin);

            return ($info && $info[0] > 0 && $info[1] > 0) ? [$info[0], $info[1]] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /* ---------------- helpers ---------------- */

    private function mm(float $px): float
    {
        return round($px * self::PX2MM, 2);
    }

    private function css(): string
    {
        return '<style>'
            .'* { box-sizing: border-box; }'
            .'body { margin:0; padding:0; }'
            .'.detail th, .detail td { vertical-align: top; word-wrap: break-word; }'
            .'</style>';
    }
}
