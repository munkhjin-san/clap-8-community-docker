<?php

namespace Tests\Feature\Learning;

use App\Models\EvaluationRecord;
use App\Services\Learning\PersonalMaterialPresentationService;
use RuntimeException;

class PersonalMaterialPresentationServiceTest extends LearningDatabaseTestCase
{
    public function test_it_uses_the_goal_period_evaluation_for_the_learner_profile(): void
    {
        EvaluationRecord::create([
            'user_id' => 7,
            'year' => 2025,
            'which_half' => 'second',
            'general_position' => '一般職',
            'current_salary_rank' => '2-8',
        ]);
        EvaluationRecord::create([
            'user_id' => 7,
            'year' => 2026,
            'which_half' => 'first',
            'general_position' => '主任',
            'current_salary_rank' => '3-2',
        ]);

        $context = app(PersonalMaterialPresentationService::class)
            ->learnerEvaluationContext(7, 2025, 'second');

        $this->assertSame([
            '職階' => '一般職',
            '等級' => '2-8',
        ], $context);
    }

    public function test_it_falls_back_to_the_latest_evaluation_for_the_learner_profile(): void
    {
        EvaluationRecord::create([
            'user_id' => 7,
            'year' => 2025,
            'which_half' => 'second',
            'general_position' => '一般職',
            'current_salary_rank' => '2-8',
        ]);
        EvaluationRecord::create([
            'user_id' => 7,
            'year' => 2026,
            'which_half' => 'first',
            'general_position' => '主任',
            'current_salary_rank' => '3-2',
        ]);

        $context = app(PersonalMaterialPresentationService::class)
            ->learnerEvaluationContext(7);

        $this->assertSame([
            '職階' => '主任',
            '等級' => '3-2',
        ], $context);
    }

    public function test_it_removes_unsupported_sampling_settings_for_gpt_56_luna(): void
    {
        $settings = app(PersonalMaterialPresentationService::class)->compatibleRequestSettings(
            [
                'top_p' => 0.98,
                'temperature' => 0.7,
                'reasoning' => ['effort' => 'medium'],
                'text' => ['verbosity' => 'medium'],
            ],
            'gpt-5.6-luna'
        );

        $this->assertArrayNotHasKey('top_p', $settings);
        $this->assertArrayNotHasKey('temperature', $settings);
        $this->assertSame(['effort' => 'medium'], $settings['reasoning']);
        $this->assertSame(['verbosity' => 'medium'], $settings['text']);
    }

    public function test_it_preserves_sampling_settings_for_other_models(): void
    {
        $settings = app(PersonalMaterialPresentationService::class)->compatibleRequestSettings(
            ['top_p' => 0.9, 'temperature' => 0.5],
            'gpt-4.1-mini'
        );

        $this->assertSame(['top_p' => 0.9, 'temperature' => 0.5], $settings);
    }

    public function test_the_schema_is_strict_and_declares_the_fixed_structure(): void
    {
        $schema = app(PersonalMaterialPresentationService::class)->slideDeckSchema();

        $this->assertSame('json_schema', $schema['type']);
        $this->assertTrue($schema['strict']);

        $sections = $schema['schema']['properties']['sections']['properties'];
        $this->assertSame(
            ['section1', 'section2', 'section3', 'section4', 'section5'],
            array_keys($sections)
        );

        $discussion = $schema['schema']['properties']['discussion']['properties'];
        $this->assertArrayHasKey('theme1', $discussion);
        $this->assertArrayHasKey('theme2', $discussion);
        $this->assertArrayHasKey('theme3', $discussion);

        $figure = $sections['section1']['properties']['figure']['properties'];
        $this->assertSame(['flow', 'list', 'concept'], $figure['type']['enum']);
    }

    public function test_it_builds_a_slide_deck_spec_and_markdown(): void
    {
        $service = app(PersonalMaterialPresentationService::class);
        $spec = $service->buildSlideDeckSpec($this->validContent(), 'ミッション・ビジョン・バリュー', '採用計画の第1版構築');

        $this->assertSame('slide_deck_v1', $spec['format']);
        $this->assertSame('ミッション・ビジョン・バリュー', $spec['selected_theme']);
        $this->assertSame('採用計画の第1版構築', $spec['goal_title']);
        $this->assertArrayHasKey('section5', $spec['sections']);
        $this->assertSame('テーマ①', $spec['discussion']['theme1']['name']);

        $markdown = $service->toMarkdown($spec);
        $this->assertStringContainsString('# 個別研修資料', $markdown);
        $this->assertStringContainsString('選択テーマ：ミッション・ビジョン・バリュー', $markdown);
        $this->assertStringContainsString('## このテーマを今回の成果目標にどう活かせるか', $markdown);
        $this->assertStringContainsString('## グループディスカッションテーマ', $markdown);
        $this->assertStringContainsString('着地の方向：まとめる', $markdown);
    }

    public function test_it_rejects_content_missing_sections(): void
    {
        $this->expectException(RuntimeException::class);

        app(PersonalMaterialPresentationService::class)
            ->buildSlideDeckSpec(['discussion' => []], 'テーマ', '目標');
    }

    public function test_it_rejects_a_discussion_theme_without_a_name(): void
    {
        $content = $this->validContent();
        $content['discussion']['theme2']['name'] = '';

        $this->expectException(RuntimeException::class);

        app(PersonalMaterialPresentationService::class)
            ->buildSlideDeckSpec($content, 'テーマ', '目標');
    }

    /**
     * @return array<string, mixed>
     */
    private function validContent(): array
    {
        $section = fn (string $type): array => [
            'body' => ['ポイント1', 'ポイント2'],
            'summary' => null,
            'figure' => [
                'type' => $type,
                'title' => '図解',
                'items' => [
                    ['label' => 'A', 'detail' => '説明A'],
                    ['label' => 'B', 'detail' => null],
                ],
                'note' => null,
            ],
        ];
        $theme = fn (string $name): array => [
            'name' => $name,
            'talk_script' => '話し言葉の本文。',
            'landing' => 'まとめる',
        ];

        return [
            'sections' => [
                'section1' => $section('flow'),
                'section2' => $section('flow'),
                'section3' => $section('list'),
                'section4' => $section('list'),
                'section5' => $section('concept'),
            ],
            'discussion' => [
                'intro' => '3つから1つを選んでください。',
                'theme1' => $theme('テーマ①'),
                'theme2' => $theme('テーマ②'),
                'theme3' => $theme('テーマ③'),
            ],
        ];
    }
}
