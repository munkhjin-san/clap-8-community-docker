<?php

namespace App\Services\Contracts;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use ZipArchive;

class ContractExtractionService
{
    private const CLAUSE_REFERENCE_PATTERN = '/第\s*[0-9０-９一二三四五六七八九十百千]+条(?:\s*の\s*[0-9０-９一二三四五六七八九十百千]+)?(?:\s*第\s*[0-9０-９一二三四五六七八九十百千]+項)?/u';
    private const CLAUSE_LINE_PATTERN = '/^\s*(第\s*[0-9０-９一二三四五六七八九十百千]+条(?:\s*の\s*[0-9０-９一二三四五六七八九十百千]+)?(?:\s*第\s*[0-9０-９一二三四五六七八九十百千]+項)?)(.*)$/u';
    private const CROSS_REFERENCE_START_PATTERN = '/^(?:[、,，.]|を|に|は|が|で|と|より|及び|又は|ならびに|並びに|第\s*[0-9０-９一二三四五六七八九十百千]+(?:号|項))/u';
    private const PARENTHETICAL_HEADING_PATTERN = '/^([（(][^）)]{0,40}[）)])\s*(.*)$/u';
    private const PARENTHETICAL_LINE_PATTERN = '/^[（(].+[）)]$/u';
    private const BULLET_LINE_PATTERN = '/^(?:[0-9０-９]+[.)、]|[①-⑳]|[一二三四五六七八九十]+[.)、]|[（(][0-9０-９一二三四五六七八九十]+[）)])/u';
    private const JAPANESE_CHAR_PATTERN = '/[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}ー々〆ヶ]/u';
    private const OPEN_PUNCTUATION_PATTERN = '/[（「『【〈《〔［｛]$/u';
    private const CLOSE_PUNCTUATION_PATTERN = '/^[、。，．）】〉》〕］｝」』：；！？]/u';

    private array $lastExtractionMetadata = [];

    public function __construct(
        private ?GeminiContractOcrService $geminiContractOcrService = null,
    ) {
    }

    public function lastExtractionMetadata(): array
    {
        return $this->lastExtractionMetadata;
    }

    public function extractIndex(string $absolutePath, string $extension): array
    {
        $extension = strtolower($extension);
        $this->lastExtractionMetadata = [
            'extension' => $extension,
            'method' => $extension === 'pdf' ? null : $extension,
        ];

        $pages = match ($extension) {
            'pdf' => $this->extractPdfPages($absolutePath),
            'docx' => $this->extractDocxPages($absolutePath),
            'txt' => $this->extractPlainTextPages($absolutePath),
            default => throw new RuntimeException('このファイル形式の比較テキスト抽出にはまだ対応していません。'),
        };

        return $this->buildDocumentIndex($pages);
    }

    public function extractPdfPages(string $absolutePath): array
    {
        $preflight = $this->inspectPdf($absolutePath);
        $this->lastExtractionMetadata = [
            'extension' => 'pdf',
            'method' => null,
            'preflight' => $preflight,
        ];

        if ($preflight['requires_ocr']) {
            return $this->extractPdfPagesWithGeminiOcr($absolutePath, $preflight['reason']);
        }

        try {
            $pages = $this->extractPdfPagesWithParserTimeout($absolutePath);
            $textLength = $this->countExtractedTextLength($pages);

            if ($textLength === 0 && ($preflight['image_count'] ?? 0) > 0) {
                return $this->extractPdfPagesWithGeminiOcr($absolutePath, 'empty_parser_result');
            }

            $this->lastExtractionMetadata['method'] = 'smalot';
            $this->lastExtractionMetadata['text_length'] = $textLength;

            return $pages;
        } catch (\Throwable $exception) {
            if ($this->isGeminiConfigured()) {
                return $this->extractPdfPagesWithGeminiOcr($absolutePath, 'parser_failed: '.$exception->getMessage());
            }

            throw new RuntimeException('PDF text extraction failed: '.$exception->getMessage(), previous: $exception);
        }
    }

    public function extractPdfPagesWithSmalot(string $absolutePath): array
    {
        $this->ensurePdfParserLoaded();

        $config = new \Smalot\PdfParser\Config();
        $config->setDataTmFontInfoHasToBeIncluded(true);

        $parser = new \Smalot\PdfParser\Parser([], $config);
        $document = $parser->parseFile($absolutePath);
        $pages = [];

        foreach ($document->getPages() as $pageNumber => $page) {
            $dataTm = $page->getDataTm();
            $tokenLines = $this->buildPdfLines($dataTm);
            $plainLines = $this->splitPlainLines($page->getText());
            $lines = $this->shouldPreferPlainTextLines($tokenLines, $plainLines, $dataTm)
                ? $plainLines
                : $tokenLines;

            if ($lines === []) {
                $lines = $plainLines;
            }

            $pages[] = [
                'page' => $pageNumber + 1,
                'lines' => $lines,
                'text' => $this->joinLines($lines),
            ];
        }

        return $pages;
    }

    public function inspectPdf(string $absolutePath): array
    {
        $contents = (string) file_get_contents($absolutePath);

        if ($contents === '') {
            throw new RuntimeException('PDF file is empty or unreadable.');
        }

        $inspectionContents = $this->appendDecodedPdfStreams($contents);
        $pageCount = preg_match_all('/\/Type\s*\/Page\b/', $inspectionContents) ?: 0;
        $fontCount = preg_match_all('/\/Type\s*\/Font\b|\/Font\s*<</', $inspectionContents) ?: 0;
        $imageCount = preg_match_all('/\/Subtype\s*\/Image\b/', $inspectionContents) ?: 0;
        $type0FontCount = preg_match_all('/\/Subtype\s*\/Type0\b/', $inspectionContents) ?: 0;
        $toUnicodeCount = substr_count($inspectionContents, '/ToUnicode');
        $japaneseFontCount = preg_match_all(
            '/90m?s?p?-RKSJ|RKSJ-H|UniJIS|Identity-H|Adobe-Japan|Ryumin|Gothic|MSPGothic/i',
            $inspectionContents
        ) ?: 0;

        $hasTextLayer = $fontCount > 0 || $type0FontCount > 0;
        $requiresOcr = false;
        $reason = 'text_pdf';

        if ($pageCount > 0 && !$hasTextLayer && $imageCount > 0) {
            $requiresOcr = true;
            $reason = 'image_only';
        } elseif ($type0FontCount > 0 && $toUnicodeCount === 0 && $japaneseFontCount > 0) {
            $requiresOcr = true;
            $reason = 'missing_unicode_map';
        } elseif ($pageCount > 0 && !$hasTextLayer) {
            $requiresOcr = true;
            $reason = 'no_text_layer';
        }

        return [
            'page_count' => $pageCount,
            'font_count' => $fontCount,
            'image_count' => $imageCount,
            'type0_font_count' => $type0FontCount,
            'to_unicode_count' => $toUnicodeCount,
            'japanese_font_count' => $japaneseFontCount,
            'encrypted' => str_contains($contents, '/Encrypt'),
            'requires_ocr' => $requiresOcr,
            'reason' => $reason,
        ];
    }

    private function appendDecodedPdfStreams(string $contents): string
    {
        if (!str_contains($contents, 'stream')) {
            return $contents;
        }

        $decodedContents = '';
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $contents, $matches);

        foreach ($matches[1] ?? [] as $stream) {
            $decoded = @zlib_decode($stream);
            if ($decoded === false) {
                $decoded = @gzuncompress($stream);
            }

            if ($decoded !== false && $decoded !== '') {
                $decodedContents .= "\n".$decoded;
            }
        }

        return $decodedContents === '' ? $contents : $contents."\n".$decodedContents;
    }

    private function extractPdfPagesWithParserTimeout(string $absolutePath): array
    {
        $timeout = max(1, (int) config('services.google.contract_pdf_parser_timeout', 15));
        $script = <<<'PHP'
require $argv[1];
try {
    $service = new \App\Services\Contracts\ContractExtractionService(null);
    echo json_encode([
        'ok' => true,
        'pages' => $service->extractPdfPagesWithSmalot($argv[2]),
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (\Throwable $exception) {
    echo json_encode([
        'ok' => false,
        'error' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit(1);
}
PHP;
        $process = new Process(
            [$this->phpCliBinary(), '-r', $script, base_path('vendor/autoload.php'), $absolutePath],
            base_path(),
            null,
            null,
            $timeout
        );

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            throw new RuntimeException("PDF parser timed out after {$timeout} seconds.", previous: $exception);
        }

        $output = trim($process->getOutput());
        $decoded = $output !== '' ? json_decode($output, true) : null;

        if (!is_array($decoded)) {
            $message = trim($process->getErrorOutput()) ?: 'PDF parser returned invalid output.';
            throw new RuntimeException($message);
        }

        if (!$process->isSuccessful() || ($decoded['ok'] ?? false) !== true) {
            $message = (string) ($decoded['error'] ?? trim($process->getErrorOutput()) ?: 'PDF parser failed.');
            throw new RuntimeException($message);
        }

        $pages = $decoded['pages'] ?? null;
        if (!is_array($pages)) {
            throw new RuntimeException('PDF parser did not return pages.');
        }

        return $pages;
    }

    private function extractPdfPagesWithGeminiOcr(string $absolutePath, string $reason): array
    {
        $pages = $this->geminiContractOcrService()->extractPdfPages($absolutePath);
        $this->lastExtractionMetadata['method'] = 'gemini_ocr';
        $this->lastExtractionMetadata['ocr_reason'] = $reason;
        $this->lastExtractionMetadata['text_length'] = $this->countExtractedTextLength($pages);

        return $pages;
    }

    private function countExtractedTextLength(array $pages): int
    {
        $text = '';

        foreach ($pages as $page) {
            $text .= (string) ($page['text'] ?? '');
        }

        return mb_strlen(trim($text), 'UTF-8');
    }

    private function isGeminiConfigured(): bool
    {
        return trim((string) config('services.google.gemini_api_key')) !== '';
    }

    private function geminiContractOcrService(): GeminiContractOcrService
    {
        if ($this->geminiContractOcrService === null) {
            $this->geminiContractOcrService = app(GeminiContractOcrService::class);
        }

        return $this->geminiContractOcrService;
    }

    private function phpCliBinary(): string
    {
        $configured = trim((string) config('services.php_cli_binary'));
        if ($configured !== '') {
            return $configured;
        }

        return PHP_SAPI === 'cli' ? PHP_BINARY : 'php';
    }

    private function extractDocxPages(string $absolutePath): array
    {
        $zip = new ZipArchive();
        $opened = $zip->open($absolutePath);

        if ($opened !== true) {
            throw new RuntimeException('DOCXファイルの読み込みに失敗しました。');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('DOCX本文を読み取れませんでした。');
        }

        return $this->buildDocxPagesFromXml($xml);
    }

    private function buildDocxPagesFromXml(string $xml): array
    {
        $document = new \DOMDocument();
        $previousLibxmlMode = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlMode);

        if (!$loaded || $document->documentElement === null) {
            return $this->buildSingleDocxPageFromXml($xml);
        }

        $pageTexts = [''];
        $this->appendDocxNodeText($document->documentElement, $pageTexts);

        return $this->buildPagesFromTexts($pageTexts);
    }

    private function appendDocxNodeText(\DOMNode $node, array &$pageTexts): void
    {
        $localName = $node->localName;

        if ($localName === 't') {
            $pageTexts[count($pageTexts) - 1] .= $node->textContent;

            return;
        }

        if ($localName === 'tab') {
            $pageTexts[count($pageTexts) - 1] .= "\t";

            return;
        }

        if ($localName === 'br') {
            if ($this->isDocxPageBreak($node)) {
                $pageTexts[] = '';
            } else {
                $pageTexts[count($pageTexts) - 1] .= "\n";
            }

            return;
        }

        if ($localName === 'lastRenderedPageBreak') {
            $pageTexts[] = '';

            return;
        }

        foreach ($node->childNodes as $childNode) {
            $this->appendDocxNodeText($childNode, $pageTexts);
        }

        if ($localName === 'p' || $localName === 'tr') {
            $pageTexts[count($pageTexts) - 1] .= "\n";
        }

        if ($localName === 'tc') {
            $pageTexts[count($pageTexts) - 1] .= "\t";
        }
    }

    private function isDocxPageBreak(\DOMNode $node): bool
    {
        if (!$node instanceof \DOMElement) {
            return false;
        }

        $type = $node->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'type')
            ?: $node->getAttribute('w:type')
            ?: $node->getAttribute('type');

        return $type === 'page';
    }

    private function buildSingleDocxPageFromXml(string $xml): array
    {
        $normalizedXml = str_replace(
            ['<w:tab/>', '<w:br/>', '<w:br />', '</w:p>', '</w:tr>', '</w:tc>'],
            ["\t", "\n", "\n", "\n", "\n", "\t"],
            $xml
        );

        $text = strip_tags($normalizedXml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $this->buildPagesFromTexts([(string) $text]);
    }

    private function buildPagesFromTexts(array $pageTexts): array
    {
        $pages = [];

        foreach ($pageTexts as $text) {
            $normalizedText = $this->normalizeContractText((string) $text);

            $pages[] = [
                'page' => count($pages) + 1,
                'lines' => $this->splitPlainLines($normalizedText),
                'text' => $normalizedText,
            ];
        }

        if ($pages !== []) {
            return $pages;
        }

        return [[
            'page' => 1,
            'lines' => [],
            'text' => '',
        ]];
    }

    private function extractPlainTextPages(string $absolutePath): array
    {
        $text = (string) file_get_contents($absolutePath);

        return [[
            'page' => 1,
            'lines' => $this->splitPlainLines($text),
            'text' => $this->normalizeContractText($text),
        ]];
    }

    private function buildDocumentIndex(array $pages): array
    {
        $clauses = [];
        $pageClauseMap = [];
        $currentClause = null;
        $currentBodyLines = [];
        $currentOrder = 0;
        $pendingClauseTitle = null;

        $flushClause = function () use (&$clauses, &$pageClauseMap, &$currentClause, &$currentBodyLines) {
            if ($currentClause === null) {
                return;
            }

            $paragraphs = $this->buildParagraphsFromLines($currentBodyLines);
            $body = trim(implode("\n", $paragraphs));
            $textParts = array_filter([
                trim($currentClause['label'].' '.$currentClause['title']),
                $body,
            ], fn ($value) => $value !== '');
            $text = trim(implode("\n", $textParts));

            $clause = [
                'id' => $currentClause['id'],
                'label' => $currentClause['label'],
                'title' => $currentClause['title'],
                'page' => $currentClause['page'],
                'order' => $currentClause['order'],
                'text' => $text,
                'body' => $body,
                'paragraphs' => $paragraphs,
                'normalizedText' => $this->normalizeCompareText($text),
                'normalizedLabel' => $this->normalizeClauseKey($currentClause['label']),
            ];

            $clauses[] = $clause;
            $pageClauseMap[$currentClause['page']][] = $clause;
            $currentClause = null;
            $currentBodyLines = [];
        };

        foreach ($pages as $page) {
            foreach ($page['lines'] as $line) {
                $line = $this->normalizeLineText($line);

                if ($line === '') {
                    if ($pendingClauseTitle !== null) {
                        if ($currentClause === null) {
                            $currentOrder++;
                            $currentClause = [
                                'id' => 'clause-'.$currentOrder,
                                'label' => '前文',
                                'title' => '',
                                'page' => $page['page'],
                                'order' => $currentOrder,
                            ];
                        }

                        $currentBodyLines[] = $pendingClauseTitle;
                        $pendingClauseTitle = null;
                    }

                    $currentBodyLines[] = '';
                    continue;
                }

                $heading = $this->parseClauseHeadingLine($line);
                if ($heading !== null) {
                    $flushClause();
                    $currentOrder++;
                    $title = $heading['title'];
                    if ($title === '' && $pendingClauseTitle !== null) {
                        $title = $pendingClauseTitle;
                    }

                    $currentClause = [
                        'id' => 'clause-'.$currentOrder,
                        'label' => $heading['label'],
                        'title' => $title,
                        'page' => $page['page'],
                        'order' => $currentOrder,
                    ];
                    $pendingClauseTitle = null;

                    if ($heading['inlineBody'] !== '') {
                        $currentBodyLines[] = $heading['inlineBody'];
                    }

                    continue;
                }

                if (preg_match(self::PARENTHETICAL_LINE_PATTERN, $line) === 1) {
                    if ($pendingClauseTitle !== null) {
                        if ($currentClause === null) {
                            $currentOrder++;
                            $currentClause = [
                                'id' => 'clause-'.$currentOrder,
                                'label' => '前文',
                                'title' => '',
                                'page' => $page['page'],
                                'order' => $currentOrder,
                            ];
                        }

                        $currentBodyLines[] = $pendingClauseTitle;
                    }

                    $pendingClauseTitle = $line;
                    continue;
                }

                if ($pendingClauseTitle !== null) {
                    if ($currentClause === null) {
                        $currentOrder++;
                        $currentClause = [
                            'id' => 'clause-'.$currentOrder,
                            'label' => '前文',
                            'title' => '',
                            'page' => $page['page'],
                            'order' => $currentOrder,
                        ];
                    }

                    $currentBodyLines[] = $pendingClauseTitle;
                    $pendingClauseTitle = null;
                }

                if ($currentClause === null) {
                    $currentOrder++;
                    $currentClause = [
                        'id' => 'clause-'.$currentOrder,
                        'label' => '前文',
                        'title' => '',
                        'page' => $page['page'],
                        'order' => $currentOrder,
                    ];
                }

                $currentBodyLines[] = $line;
            }
        }

        $flushClause();

        if ($clauses === []) {
            $fullText = trim(implode("\n\n", array_map(fn (array $page) => $page['text'], $pages)));
            $paragraphs = $this->buildParagraphsFromLines($this->splitPlainLines($fullText));
            $fallback = [
                'id' => 'clause-1',
                'label' => '本文',
                'title' => '',
                'page' => 1,
                'order' => 1,
                'text' => $fullText,
                'body' => trim(implode("\n", $paragraphs)),
                'paragraphs' => $paragraphs,
                'normalizedText' => $this->normalizeCompareText($fullText),
                'normalizedLabel' => $this->normalizeClauseKey('本文'),
            ];
            $clauses[] = $fallback;
            $pageClauseMap[1] = [$fallback];
        }

        $indexedPages = [];
        foreach ($pages as $page) {
            $indexedPages[] = [
                'page' => $page['page'],
                'text' => $page['text'],
                'normalizedText' => $this->normalizeCompareText($page['text']),
                'clauses' => $pageClauseMap[$page['page']] ?? [],
            ];
        }

        return [
            'pageCount' => count($pages),
            'pages' => $indexedPages,
            'clauses' => $clauses,
        ];
    }

    private function buildPdfLines(array $dataTm): array
    {
        $tokens = [];

        foreach ($dataTm as $row) {
            $matrix = $row[0] ?? null;
            $text = $this->normalizePdfToken((string) ($row[1] ?? ''));

            if (!is_array($matrix) || $text === '') {
                continue;
            }

            $tokens[] = [
                'text' => $text,
                'x' => (float) ($matrix[4] ?? 0),
                'y' => (float) ($matrix[5] ?? 0),
                'fontSize' => (float) ($row[3] ?? 10),
            ];
        }

        if ($tokens === []) {
            return [];
        }

        usort($tokens, function (array $left, array $right) {
            if (abs($left['y'] - $right['y']) > 1.2) {
                return $left['y'] < $right['y'] ? 1 : -1;
            }

            return $left['x'] <=> $right['x'];
        });

        $lineGroups = [];
        foreach ($tokens as $token) {
            $groupIndex = count($lineGroups) - 1;
            $tolerance = max(2.0, $token['fontSize'] * 0.35);

            if ($groupIndex >= 0 && abs($lineGroups[$groupIndex]['y'] - $token['y']) <= $tolerance) {
                $lineGroups[$groupIndex]['tokens'][] = $token;
                $lineGroups[$groupIndex]['ySamples'][] = $token['y'];
                continue;
            }

            $lineGroups[] = [
                'y' => $token['y'],
                'ySamples' => [$token['y']],
                'tokens' => [$token],
            ];
        }

        $lines = [];
        foreach ($lineGroups as $group) {
            usort($group['tokens'], fn (array $left, array $right) => $left['x'] <=> $right['x']);

            $deduped = [];
            foreach ($group['tokens'] as $token) {
                $previous = $deduped[count($deduped) - 1] ?? null;
                if (
                    $previous !== null
                    && $this->normalizeCompareText($previous['text']) === $this->normalizeCompareText($token['text'])
                    && abs($previous['x'] - $token['x']) <= 4.0
                ) {
                    continue;
                }

                $deduped[] = $token;
            }

            $compacted = [];
            foreach ($deduped as $token) {
                $previous = $compacted[count($compacted) - 1] ?? null;
                if ($previous !== null && abs($previous['x'] - $token['x']) <= 1.2) {
                    if (str_starts_with($token['text'], $previous['text'])) {
                        $compacted[count($compacted) - 1] = $token;
                        continue;
                    }

                    if (str_starts_with($previous['text'], $token['text'])) {
                        continue;
                    }
                }

                $compacted[] = $token;
            }

            $text = '';
            foreach ($compacted as $token) {
                $text = $this->appendWithOverlap($text, $token['text']);
            }

            $lineText = $this->normalizeLineText($text);
            if ($lineText === '') {
                continue;
            }

            $lines[] = [
                'text' => $lineText,
                'y' => array_sum($group['ySamples']) / count($group['ySamples']),
            ];
        }

        $pageLines = [];
        $gaps = [];
        for ($index = 0; $index < count($lines) - 1; $index++) {
            $gap = $lines[$index]['y'] - $lines[$index + 1]['y'];
            if ($gap > 0.5) {
                $gaps[] = $gap;
            }
        }

        sort($gaps);
        $medianGap = $gaps === [] ? 12.0 : $gaps[(int) floor(count($gaps) / 2)];
        $paragraphGap = max(18.0, $medianGap * 1.45);

        foreach ($lines as $index => $line) {
            $pageLines[] = $line['text'];

            if ($index >= count($lines) - 1) {
                continue;
            }

            $gap = $line['y'] - $lines[$index + 1]['y'];
            if ($gap > $paragraphGap) {
                $pageLines[] = '';
            }
        }

        return $pageLines;
    }

    private function splitPlainLines(string $text): array
    {
        $normalized = str_replace("\r", '', $text);
        $normalized = preg_replace("/\n{3,}/u", "\n\n", $normalized) ?? $normalized;

        return array_map(
            fn (string $line) => $this->normalizeLineText($line),
            preg_split("/\n/u", $normalized) ?: []
        );
    }

    private function shouldPreferPlainTextLines(array $tokenLines, array $plainLines, array $dataTm): bool
    {
        $countNonEmpty = static fn (array $lines) => count(array_filter(
            $lines,
            static fn ($line) => trim((string) $line) !== ''
        ));

        $tokenCount = $countNonEmpty($tokenLines);
        $plainCount = $countNonEmpty($plainLines);

        if ($plainCount === 0) {
            return false;
        }

        if ($tokenCount === 0) {
            return true;
        }

        if (count($dataTm) <= 3 && $plainCount > $tokenCount) {
            return true;
        }

        return $tokenCount === 1 && $plainCount >= 4;
    }

    private function joinLines(array $lines): string
    {
        return trim(preg_replace("/\n{3,}/u", "\n\n", implode("\n", $lines)) ?? implode("\n", $lines));
    }

    private function buildParagraphsFromLines(array $lines): array
    {
        $paragraphs = [];
        $current = '';

        $flush = function () use (&$paragraphs, &$current) {
            $current = trim($current);
            if ($current !== '') {
                $paragraphs[] = $current;
            }
            $current = '';
        };

        foreach ($lines as $line) {
            $line = $this->normalizeLineText($line);

            if ($line === '') {
                $flush();
                continue;
            }

            $startsNewParagraph = $current === ''
                || preg_match(self::BULLET_LINE_PATTERN, $line) === 1
                || preg_match(self::PARENTHETICAL_LINE_PATTERN, $line) === 1;

            if ($startsNewParagraph) {
                $flush();
                $current = $line;
                continue;
            }

            $current .= $this->shouldJoinWithoutSpace($current, $line) ? '' : ' ';
            $current .= $line;
        }

        $flush();

        return $paragraphs;
    }

    private function parseClauseHeadingLine(string $line): ?array
    {
        $line = $this->normalizeLineText($line);

        if ($line === '' || preg_match(self::CLAUSE_LINE_PATTERN, $line, $matches) !== 1) {
            return null;
        }

        $label = trim($matches[1]);
        $remainder = trim($matches[2] ?? '');

        if ($remainder !== '' && preg_match(self::CROSS_REFERENCE_START_PATTERN, $remainder) === 1) {
            return null;
        }

        $title = '';
        $inlineBody = $remainder;

        if ($remainder !== '' && preg_match(self::PARENTHETICAL_HEADING_PATTERN, $remainder, $titleMatch) === 1) {
            $title = trim($titleMatch[1]);
            $inlineBody = trim($titleMatch[2] ?? '');
        } elseif ($remainder !== '' && preg_match(self::PARENTHETICAL_LINE_PATTERN, $remainder) === 1) {
            $title = $remainder;
            $inlineBody = '';
        }

        return [
            'label' => $label,
            'title' => $title,
            'inlineBody' => $inlineBody,
        ];
    }

    private function normalizeContractText(?string $value): string
    {
        $text = $value ?? '';
        $text = str_replace("\r", '', $text);
        $text = str_replace("\u{3000}", ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n[ \t]+/u', "\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+\n/u', "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function normalizeCompareText(?string $value): string
    {
        $text = $this->normalizeContractText($value);
        $text = $this->normalizeWidthVariants($text);
        $text = preg_replace('/\s+/u', '', $text) ?? $text;

        return mb_strtolower($text, 'UTF-8');
    }

    private function normalizeClauseKey(?string $value): string
    {
        $reference = $this->extractClauseReference($value);
        $reference = $reference !== '' ? $reference : (string) ($value ?? '');
        $reference = $this->normalizeWidthVariants($reference);
        $reference = preg_replace_callback('/第([一二三四五六七八九十百千]+)(条|項)/u', function (array $matches) {
            $number = $this->convertJapaneseNumber($matches[1]);

            return $number !== null ? '第'.$number.$matches[2] : $matches[0];
        }, $reference) ?? $reference;

        return $this->normalizeCompareText($reference);
    }

    private function extractClauseReference(?string $value): string
    {
        $text = $this->normalizeContractText($value);

        return preg_match(self::CLAUSE_REFERENCE_PATTERN, $text, $matches) === 1
            ? trim($matches[0])
            : '';
    }

    private function normalizeWidthVariants(string $value): string
    {
        if (!class_exists(\Normalizer::class)) {
            return $value;
        }

        return \Normalizer::normalize($value, \Normalizer::FORM_KC) ?: $value;
    }

    private function normalizePdfToken(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);
        $value = preg_replace('/\s+/u', '', $value) ?? $value;
        $value = $this->normalizeWidthVariants(trim($value));
        $value = $this->collapseRepeatedSingleCharacterToken($value);
        $value = $this->collapseRepeatedPhraseToken($value);

        return trim($value);
    }

    private function normalizeLineText(string $value): string
    {
        $value = $this->normalizeWidthVariants($value);
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? $value;
        $value = preg_replace(
            '/(?<=[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}ー々〆ヶ])\s+(?=[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}ー々〆ヶ])/u',
            '',
            $value
        ) ?? $value;
        $value = preg_replace('/\s+([、。，．)\]）】〉》〕］｝」』：；！？])/u', '$1', $value) ?? $value;
        $value = preg_replace('/([(\"（「『【〈《〔［｛])\s+/u', '$1', $value) ?? $value;
        $value = preg_replace_callback(
            '/第\s*([0-9０-９一二三四五六七八九十百千 ]+)\s*(条|項)/u',
            function (array $matches) {
                $number = preg_replace('/\s+/u', '', $matches[1] ?? '') ?? '';

                return '第'.$number.($matches[2] ?? '');
            },
            $value
        ) ?? $value;
        $value = preg_replace_callback(
            '/[（(]\s*([0-9０-９ ]+)\s*[）)]/u',
            function (array $matches) {
                $number = preg_replace('/\s+/u', '', $matches[1] ?? '') ?? '';

                return '('.$number.')';
            },
            $value
        ) ?? $value;

        return trim($value);
    }

    private function shouldJoinWithoutSpace(string $previousText, string $nextText): bool
    {
        $previousLast = mb_substr($previousText, -1, 1, 'UTF-8');
        $nextFirst = mb_substr($nextText, 0, 1, 'UTF-8');

        if (
            preg_match('/第[0-9０-９一二三四五六七八九十百千]*$/u', $previousText) === 1
            && preg_match('/^[0-9０-９一二三四五六七八九十百千]+(?:条|項)/u', $nextText) === 1
        ) {
            return true;
        }

        return preg_match(self::JAPANESE_CHAR_PATTERN, $previousLast) === 1
            || preg_match(self::JAPANESE_CHAR_PATTERN, $nextFirst) === 1
            || preg_match(self::OPEN_PUNCTUATION_PATTERN, $previousText) === 1
            || preg_match(self::CLOSE_PUNCTUATION_PATTERN, $nextText) === 1;
    }

    private function appendWithOverlap(string $current, string $next): string
    {
        if ($current === '') {
            return $next;
        }

        $maxOverlap = min(mb_strlen($current, 'UTF-8'), mb_strlen($next, 'UTF-8'));
        for ($length = $maxOverlap; $length >= 1; $length--) {
            $currentTail = mb_substr($current, -$length, null, 'UTF-8');
            $nextHead = mb_substr($next, 0, $length, 'UTF-8');

            if ($currentTail === $nextHead) {
                return $current.mb_substr($next, $length, null, 'UTF-8');
            }
        }

        return $current.($this->shouldJoinWithoutSpace($current, $next) ? '' : ' ').$next;
    }

    private function collapseRepeatedSingleCharacterToken(string $value): string
    {
        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $count = count($chars);

        if ($count < 2) {
            return $value;
        }

        $first = $chars[0];
        foreach ($chars as $char) {
            if ($char !== $first) {
                return $value;
            }
        }

        if (preg_match('/^\d$/u', $first) === 1) {
            return $count >= 3 ? $first : $value;
        }

        if (preg_match(self::JAPANESE_CHAR_PATTERN, $first) === 1) {
            return $count >= 5 ? str_repeat($first, 2) : $value;
        }

        return $value;
    }

    private function collapseRepeatedPhraseToken(string $value): string
    {
        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $length = count($chars);

        if ($length < 4 || $length > 24) {
            return $value;
        }

        for ($unitLength = 1; $unitLength <= intdiv($length, 2); $unitLength++) {
            if ($length % $unitLength !== 0) {
                continue;
            }

            $unit = implode('', array_slice($chars, 0, $unitLength));
            if (preg_match('/^\d+$/u', $unit) === 1) {
                continue;
            }

            if (str_repeat($unit, (int) ($length / $unitLength)) === $value) {
                return $unit;
            }
        }

        return $value;
    }

    private function convertJapaneseNumber(string $value): ?int
    {
        $digitMap = [
            '〇' => 0,
            '一' => 1,
            '二' => 2,
            '三' => 3,
            '四' => 4,
            '五' => 5,
            '六' => 6,
            '七' => 7,
            '八' => 8,
            '九' => 9,
        ];
        $unitMap = [
            '十' => 10,
            '百' => 100,
            '千' => 1000,
        ];

        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $total = 0;
        $current = 0;

        foreach ($chars as $char) {
            if (array_key_exists($char, $digitMap)) {
                $current = $digitMap[$char];
                continue;
            }

            if (array_key_exists($char, $unitMap)) {
                $multiplier = $current === 0 ? 1 : $current;
                $total += $multiplier * $unitMap[$char];
                $current = 0;
                continue;
            }

            return null;
        }

        return $total + $current;
    }

    private function ensurePdfParserLoaded(): void
    {
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            return;
        }

        $fallback = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'smalot'.DIRECTORY_SEPARATOR.'pdfparser'.DIRECTORY_SEPARATOR.'alt_autoload.php-dist';
        if (is_file($fallback)) {
            require_once $fallback;
        }

        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new RuntimeException('PDF parser is not available.');
        }
    }
}
