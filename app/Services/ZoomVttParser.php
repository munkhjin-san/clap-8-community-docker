<?php

namespace App\Services;

class ZoomVttParser
{
    /**
     * Convert a Zoom WebVTT transcript into timestamped, speaker-aware cues.
     *
     * @return array<int, array{start: string, end: string, speaker: ?string, text: string}>
     */
    public function parse(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $content = str_replace(["\r\n", "\r"], "\n", trim($content));
        $blocks = preg_split('/\n{2,}/', $content) ?: [];
        $cues = [];

        foreach ($blocks as $block) {
            $lines = array_values(array_filter(
                array_map('trim', explode("\n", $block)),
                static fn (string $line) => $line !== ''
            ));

            if ($lines === [] || $lines[0] === 'WEBVTT') {
                continue;
            }

            $timingIndex = $this->timingLineIndex($lines);

            if ($timingIndex === null) {
                continue;
            }

            if (! preg_match(
                '/^(?<start>(?:\d{2}:)?\d{2}:\d{2}\.\d{3})\s+-->\s+(?<end>(?:\d{2}:)?\d{2}:\d{2}\.\d{3})/',
                $lines[$timingIndex],
                $matches
            )) {
                continue;
            }

            $text = trim(implode("\n", array_slice($lines, $timingIndex + 1)));
            [$speaker, $text] = $this->extractSpeaker($text);
            $text = trim(strip_tags($text));

            if ($text === '') {
                continue;
            }

            $cues[] = [
                'start' => $matches['start'],
                'end' => $matches['end'],
                'speaker' => $speaker,
                'text' => $text,
            ];
        }

        return $cues;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function timingLineIndex(array $lines): ?int
    {
        foreach ($lines as $index => $line) {
            if (str_contains($line, '-->')) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    private function extractSpeaker(string $text): array
    {
        if (preg_match('/^<v(?:\.[^ >]+)*\s+([^>]+)>(.*)<\/v>$/su', $text, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        if (preg_match('/^([^:\n]{1,80})[:：]\s*(.+)$/su', $text, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        return [null, $text];
    }
}
