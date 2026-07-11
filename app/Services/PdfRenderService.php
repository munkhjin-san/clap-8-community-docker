<?php

namespace App\Services;

use App\Models\FlowDefinition;
use App\Models\FlowRecord;
use Carbon\Carbon;
use Mpdf\Mpdf;

/**
 * Renders a "PDF tool" template (visual A4 canvas, free positioning) for a single record.
 *
 * Layout model (reconciles free-canvas with multi-page):
 *   - Elements above the 明細 table  → written at exact page coordinates on page 1.
 *   - 明細 table                     → flowing <table> with a repeating <thead>; paginates.
 *   - Elements at/below the table    → written at page coordinates right after the table ends,
 *                                      keeping their relative layout to each other.
 *   - Running page footer            → page number, repeated on every page.
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
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $landscape ? 'A4-L' : 'A4',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 12,
            'margin_footer' => 5,
        ]);
        $mpdf->SetHTMLFooter('<div style="text-align:center; font-size:8pt; color:#9aa1ac;">{PAGENO} / {nbpg}</div>');

        $elements = array_values($template['elements'] ?? []);
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

        // make sure page 1 exists before any fixed-position write
        $mpdf->WriteHTML($this->css().'<div></div>');

        // ---- page-1 header zone: exact page coordinates ----
        foreach ($header as $el) {
            $this->writeFixedElement($mpdf, $el, $byKey, $this->mm((float) ($el['y'] ?? 0)));
        }

        if ($table) {
            // ---- 明細 table: flows below the header zone, paginates with repeating thead ----
            $mpdf->WriteHTML(
                $this->css()
                .'<div style="height:'.$this->mm($tableTop).'mm;"></div>'
                .$this->renderTable($table, $byKey)
            );

            // ---- after-table zone: keeps relative layout, placed after wherever the table ended ----
            if ($after) {
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
        }

        return $mpdf;
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
                return '<div style="'.$this->textCss($style).'">'.e($this->formatValue($byKey[$el['fieldKey'] ?? ''] ?? null, $el)).'</div>';
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

        $wrap = "margin-left:{$x}mm; width:{$w}mm;";
        $html = "<div style=\"{$wrap}\">";
        $html .= "<table class=\"detail\" style=\"width:100%; border-collapse:collapse; font-size:{$cellPt}pt;\">";

        // header row (repeats on page break)
        $html .= '<thead><tr>';
        foreach ($columns as $c) {
            $cw = isset($c['width']) && $c['width'] ? 'width:'.(float) $c['width'].'%;' : '';
            $al = $this->safeAlign($c['align'] ?? 'left');
            $html .= "<th style=\"{$cw} text-align:{$al}; background:{$headBg}; border:1px solid {$borderColor}; padding:4px 6px; font-weight:700;\">".e((string) ($c['label'] ?? '')).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        // body rows
        $subtotal = 0.0;
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columns as $c) {
                $al = $this->safeAlign($c['align'] ?? 'left');
                $raw = is_array($row) ? ($row[$c['colKey'] ?? ''] ?? null) : null;
                $txt = e($this->formatScalar($raw, $c['format'] ?? null, $c));
                $html .= "<td style=\"text-align:{$al}; border:1px solid {$borderColor}; padding:4px 6px;\">{$txt}</td>";
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
