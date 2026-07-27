<?php

namespace App\Services\Learning;

use App\Models\EvaluationRecord;
use DOMDocument;
use DOMElement;
use DOMXPath;
use JsonException;
use RuntimeException;

class PersonalMaterialPresentationService
{
    /**
     * Remove sampling controls that GPT-5.6 Luna does not accept.
     *
     * Theme-level settings remain the source of truth for supported options,
     * including reasoning effort, verbosity, and output-token limits.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function compatibleRequestSettings(array $settings, string $model): array
    {
        if (str_starts_with($model, 'gpt-5.6-luna')) {
            unset($settings['top_p'], $settings['temperature']);
        }

        return $settings;
    }

    /**
     * @return array{職階: string|null, 等級: string|null}
     */
    public function learnerEvaluationContext(
        int $userId,
        ?int $year = null,
        ?string $whichHalf = null
    ): array {
        $evaluation = null;

        if ($year !== null && filled($whichHalf)) {
            $evaluation = EvaluationRecord::query()
                ->where('user_id', $userId)
                ->where('year', $year)
                ->where('which_half', $whichHalf)
                ->orderByDesc('id')
                ->first(['general_position', 'current_salary_rank']);
        }

        $evaluation ??= EvaluationRecord::query()
            ->where('user_id', $userId)
            ->orderByDesc('year')
            ->orderByRaw(
                "CASE which_half WHEN 'second' THEN 2 WHEN 'first' THEN 1 ELSE 0 END DESC"
            )
            ->orderByDesc('id')
            ->first(['general_position', 'current_salary_rank']);

        return [
            '職階' => $evaluation?->general_position,
            '等級' => $evaluation?->current_salary_rank,
        ];
    }

    /**
     * Select one shared light color for the complete lifetime of a generation.
     */
    public function randomAccentColor(): string
    {
        try {
            $colors = json_decode(
                (string) file_get_contents(resource_path('assets/colors.json')),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('アクセントカラー設定を読み込めませんでした。', 0, $exception);
        }

        $lightColors = collect($colors)
            ->pluck('light')
            ->filter(fn ($color) => is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color))
            ->values();

        if ($lightColors->isEmpty()) {
            throw new RuntimeException('プレゼンテーション用のアクセントカラーがありません。');
        }

        return $lightColors->get(random_int(0, $lightColors->count() - 1));
    }

    /**
     * Convert the model's complete HTML document into the persisted presentation envelope.
     *
     * @return array{html: string, title: string, summary: string}
     */
    public function parseHtmlResponse(string $response): array
    {
        $html = $this->extractHtmlDocument($response);
        $document = $this->loadHtml($html);
        $xpath = new DOMXPath($document);

        $sceneQuery = '//main[contains(concat(" ", normalize-space(@class), " "), " story ")]'
            .'/section[contains(concat(" ", normalize-space(@class), " "), " scene ")]';
        $slideCount = $xpath->query($sceneQuery)?->length ?? 0;
        if (
            ! $xpath->query('//style')?->length
            || ! $xpath->query('//body')?->length
            || $slideCount < 6
            || $slideCount > 9
        ) {
            throw new RuntimeException('生成されたプレゼンテーションHTMLに必要な要素がありません。');
        }

        $discussionSections = $xpath->query(
            '//section[@id="group-discussion"]'
            .'[contains(concat(" ", normalize-space(@class), " "), " scene ")]'
        );
        if (($discussionSections?->length ?? 0) !== 1) {
            throw new RuntimeException(
                'グループディスカッション用sceneには、固定IDとclassが必要です。'
            );
        }

        $discussionSection = $discussionSections?->item(0);
        $discussionThemes = $discussionSection
            ? $xpath->query(
                './/article[contains(concat(" ", normalize-space(@class), " "), " discussion-theme ")]',
                $discussionSection
            )
            : null;
        if (($discussionThemes?->length ?? 0) !== 3) {
            throw new RuntimeException(
                'グループディスカッション用テーマは、指定されたHTML構造でちょうど3つ必要です。'
            );
        }

        foreach ($discussionThemes ?: [] as $index => $discussionTheme) {
            if (! $discussionTheme instanceof DOMElement) {
                continue;
            }

            $expectedNumber = (string) ($index + 1);
            $heading = $xpath->query('.//h3', $discussionTheme)?->item(0);
            $question = $xpath->query(
                './/*[contains(concat(" ", normalize-space(@class), " "), " discussion-question ")]',
                $discussionTheme
            )?->item(0);

            if (
                $discussionTheme->getAttribute('data-theme-number') !== $expectedNumber
                || ! $heading
                || $this->normalizeText($heading->textContent) === ''
                || ! $question
                || $this->normalizeText($question->textContent) === ''
            ) {
                throw new RuntimeException(
                    '各グループディスカッション用テーマには、連番、見出し、問いが必要です。'
                );
            }
        }

        $title = $this->firstText($xpath, '//h1')
            ?: $this->firstText($xpath, '//title')
            ?: '個人専用研修資料';
        $summary = $this->firstText($xpath, '//h1/following::p[1]')
            ?: $this->firstText($xpath, '//main//p | //body//p');

        return [
            'html' => $html,
            'title' => $title,
            'summary' => $summary,
        ];
    }

    /**
     * Preserve the existing text-based feedback flow with a semantic Markdown copy.
     *
     * @param  array{html?: mixed}  $presentation
     */
    public function toMarkdown(array $presentation): string
    {
        if (! is_string($presentation['html'] ?? null)) {
            throw new RuntimeException('プレゼンテーションHTMLがありません。');
        }

        $document = $this->loadHtml($presentation['html']);
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query(
            '//body//*[self::h1 or self::h2 or self::h3 or self::p or self::li '
            .'or self::blockquote or self::figcaption or self::dt or self::dd]'
            .'[not(ancestor::li) and not(ancestor::blockquote)]'
        );
        $lines = [];
        $previousTag = null;

        foreach ($nodes ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $text = $this->normalizeText($node->textContent);
            if ($text === '') {
                continue;
            }

            $line = match (strtolower($node->tagName)) {
                'h1' => '# '.$text,
                'h2' => '## '.$text,
                'h3' => '### '.$text,
                'li' => '- '.$text,
                'blockquote' => '> '.$text,
                default => $text,
            };

            $tag = strtolower($node->tagName);
            if ($lines !== [] && ! ($tag === 'li' && $previousTag === 'li')) {
                $lines[] = '';
            }
            $lines[] = $line;
            $previousTag = $tag;
        }

        if ($lines === []) {
            throw new RuntimeException('生成されたHTMLから研修資料本文を抽出できませんでした。');
        }

        return implode("\n", $lines);
    }

    private function extractHtmlDocument(string $response): string
    {
        $html = trim($response);
        $html = preg_replace('/\A```(?:html)?\s*/i', '', $html) ?? $html;
        $html = preg_replace('/\s*```\z/', '', $html) ?? $html;

        $htmlStart = stripos($html, '<html');
        $doctypeStart = stripos($html, '<!doctype');
        $start = $doctypeStart !== false ? $doctypeStart : $htmlStart;
        $end = strripos($html, '</html>');

        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException('OpenAIから完全なHTMLドキュメントが返されませんでした。');
        }

        $html = substr($html, $start, $end + strlen('</html>') - $start);

        if (strlen($html) < 500) {
            throw new RuntimeException('生成されたプレゼンテーションHTMLが短すぎます。');
        }

        return $html;
    }

    private function loadHtml(string $html): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8">'.$html,
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (! $loaded) {
            throw new RuntimeException('生成されたプレゼンテーションHTMLを解析できませんでした。');
        }

        return $document;
    }

    private function firstText(DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)?->item(0);

        return $node ? $this->normalizeText($node->textContent) : '';
    }

    private function normalizeText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
