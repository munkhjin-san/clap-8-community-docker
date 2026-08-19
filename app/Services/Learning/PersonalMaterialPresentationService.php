<?php

namespace App\Services\Learning;

use App\Models\EvaluationRecord;
use RuntimeException;

class PersonalMaterialPresentationService
{
    /** Fixed section titles — the format's slots, not AI-authored. */
    public const SECTION_TITLES = [
        'section1' => 'このテーマを今回の成果目標にどう活かせるか',
        'section2' => '成果目標達成に向けて本人が理解すべき考え方',
        'section3' => '過去の自分から見える強み',
        'section4' => '逆に注意すべき点',
        'section5' => '達成に向けて意識したい具体的な行動',
    ];

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
     * OpenAI Responses API structured-output schema. The model returns only the
     * content (sections + discussion); selected_theme/goal_title are added by
     * the caller from known data. Strict mode: every property required,
     * additionalProperties false, optionals modelled as nullable.
     *
     * @return array<string, mixed>
     */
    public function slideDeckSchema(): array
    {
        $figureItem = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['label', 'detail'],
            'properties' => [
                'label' => ['type' => 'string'],
                'detail' => ['type' => ['string', 'null']],
            ],
        ];
        $section = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['body', 'summary', 'figure'],
            'properties' => [
                'body' => ['type' => 'array', 'items' => ['type' => 'string']],
                'summary' => ['type' => ['string', 'null']],
                'figure' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['type', 'title', 'items', 'note'],
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['flow', 'list', 'concept']],
                        'title' => ['type' => ['string', 'null']],
                        'items' => ['type' => 'array', 'items' => $figureItem],
                        'note' => ['type' => ['string', 'null']],
                    ],
                ],
            ],
        ];
        $theme = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['name', 'talk_script', 'landing'],
            'properties' => [
                'name' => ['type' => 'string'],
                'talk_script' => ['type' => 'string'],
                'landing' => ['type' => 'string'],
            ],
        ];

        return [
            'type' => 'json_schema',
            'name' => 'personal_material_slide_deck',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['sections', 'discussion'],
                'properties' => [
                    'sections' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['section1', 'section2', 'section3', 'section4', 'section5'],
                        'properties' => [
                            'section1' => $section,
                            'section2' => $section,
                            'section3' => $section,
                            'section4' => $section,
                            'section5' => $section,
                        ],
                    ],
                    'discussion' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['intro', 'theme1', 'theme2', 'theme3'],
                        'properties' => [
                            'intro' => ['type' => 'string'],
                            'theme1' => $theme,
                            'theme2' => $theme,
                            'theme3' => $theme,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Validate the model's structured content and assemble the persisted spec.
     *
     * @param  array<string, mixed>  $data  Decoded model output (sections + discussion).
     * @return array<string, mixed>
     */
    public function buildSlideDeckSpec(array $data, string $selectedTheme, string $goalTitle): array
    {
        $sections = $data['sections'] ?? null;
        $discussion = $data['discussion'] ?? null;

        if (! is_array($sections) || ! is_array($discussion)) {
            throw new RuntimeException('生成された研修資料の構造が不正です。');
        }

        foreach (array_keys(self::SECTION_TITLES) as $key) {
            $section = $sections[$key] ?? null;
            if (! is_array($section) || ! is_array($section['body'] ?? null) || ! is_array($section['figure'] ?? null)) {
                throw new RuntimeException("研修資料の{$key}が不正です。");
            }
        }

        foreach (['theme1', 'theme2', 'theme3'] as $key) {
            $theme = $discussion[$key] ?? null;
            if (! is_array($theme) || blank($theme['name'] ?? null)) {
                throw new RuntimeException("グループディスカッションの{$key}が不正です。");
            }
        }

        return [
            'format' => 'slide_deck_v1',
            'selected_theme' => $selectedTheme,
            'goal_title' => $goalTitle,
            'sections' => $sections,
            'discussion' => $discussion,
        ];
    }

    /**
     * Semantic Markdown copy of the deck, used by the text/feedback flow.
     *
     * @param  array<string, mixed>  $spec
     */
    public function toMarkdown(array $spec): string
    {
        $lines = ['# 個別研修資料'];
        if (filled($spec['selected_theme'] ?? null)) {
            $lines[] = '';
            $lines[] = '選択テーマ：'.$spec['selected_theme'];
        }
        if (filled($spec['goal_title'] ?? null)) {
            $lines[] = '';
            $lines[] = '「'.$spec['goal_title'].'」を達成するために';
        }

        $sections = $spec['sections'] ?? [];
        foreach (self::SECTION_TITLES as $key => $title) {
            $section = $sections[$key] ?? [];
            $lines[] = '';
            $lines[] = '## '.$title;
            foreach (($section['body'] ?? []) as $bullet) {
                if (filled($bullet)) {
                    $lines[] = '- '.$bullet;
                }
            }
            $figure = $section['figure'] ?? [];
            if (filled($figure['title'] ?? null)) {
                $lines[] = '';
                $lines[] = '### '.$figure['title'];
            }
            foreach (($figure['items'] ?? []) as $item) {
                $label = $item['label'] ?? '';
                if (blank($label)) {
                    continue;
                }
                $lines[] = filled($item['detail'] ?? null) ? "- {$label}：{$item['detail']}" : "- {$label}";
            }
            if (filled($figure['note'] ?? null)) {
                $lines[] = $figure['note'];
            }
            if (filled($section['summary'] ?? null)) {
                $lines[] = '';
                $lines[] = '> '.$section['summary'];
            }
        }

        $discussion = $spec['discussion'] ?? [];
        $lines[] = '';
        $lines[] = '## グループディスカッションテーマ';
        if (filled($discussion['intro'] ?? null)) {
            $lines[] = $discussion['intro'];
        }
        foreach (['theme1', 'theme2', 'theme3'] as $key) {
            $theme = $discussion[$key] ?? [];
            if (blank($theme['name'] ?? null)) {
                continue;
            }
            $lines[] = '';
            $lines[] = '### '.$theme['name'];
            if (filled($theme['talk_script'] ?? null)) {
                $lines[] = '話し言葉：'.$theme['talk_script'];
            }
            if (filled($theme['landing'] ?? null)) {
                $lines[] = '着地の方向：'.$theme['landing'];
            }
        }

        $lines[] = '';
        $lines[] = '## お疲れ様でした。';

        return implode("\n", $lines);
    }
}
