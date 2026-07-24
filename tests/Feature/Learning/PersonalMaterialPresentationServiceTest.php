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

    public function test_it_parses_generated_html_and_preserves_a_markdown_copy(): void
    {
        $service = app(PersonalMaterialPresentationService::class);
        $presentation = $service->parseHtmlResponse($this->presentationHtml());
        $markdown = $service->toMarkdown($presentation);

        $this->assertSame('経験を次の行動へ', $presentation['title']);
        $this->assertSame('前回の経験を、新しい実践へつなげるための研修です。', $presentation['summary']);
        $this->assertStringContainsString('<style>', $presentation['html']);
        $this->assertStringContainsString('# 経験を次の行動へ', $markdown);
        $this->assertStringContainsString('## グループディスカッション', $markdown);
        $this->assertStringContainsString('- 明日から変える行動は何か', $markdown);
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

    public function test_it_selects_a_light_accent_color_from_the_shared_palette(): void
    {
        $colors = json_decode(
            (string) file_get_contents(resource_path('assets/colors.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $lightColors = collect($colors)->pluck('light')->all();

        $accentColor = app(PersonalMaterialPresentationService::class)->randomAccentColor();

        $this->assertContains($accentColor, $lightColors);
    }

    public function test_it_rejects_an_incomplete_html_response(): void
    {
        $this->expectException(RuntimeException::class);

        app(PersonalMaterialPresentationService::class)->parseHtmlResponse(
            '<html><body><h1>途中で切れた資料'
        );
    }

    public function test_it_rejects_a_presentation_without_exactly_three_discussion_themes(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ちょうど3つ');

        $html = str_replace(
            $this->discussionThemeHtml(3),
            '',
            $this->presentationHtml()
        );

        app(PersonalMaterialPresentationService::class)->parseHtmlResponse($html);
    }

    public function test_it_rejects_a_discussion_section_without_its_fixed_selector_attributes(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('固定IDとclass');

        $html = str_replace(
            '<section id="group-discussion" class="scene">',
            '<section id="discussion" class="scene">',
            $this->presentationHtml()
        );

        app(PersonalMaterialPresentationService::class)->parseHtmlResponse($html);
    }

    public function test_it_rejects_a_discussion_theme_without_its_fixed_class(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ちょうど3つ');

        $html = str_replace(
            '<article class="discussion-theme" data-theme-number="2">',
            '<article class="theme" data-theme-number="2">',
            $this->presentationHtml()
        );

        app(PersonalMaterialPresentationService::class)->parseHtmlResponse($html);
    }

    public function test_it_rejects_a_discussion_theme_without_its_fixed_number(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('連番、見出し、問い');

        $html = str_replace(
            'data-theme-number="2"',
            'data-theme-number="9"',
            $this->presentationHtml()
        );

        app(PersonalMaterialPresentationService::class)->parseHtmlResponse($html);
    }

    private function presentationHtml(): string
    {
        $discussionTheme1 = $this->discussionThemeHtml(1);
        $discussionTheme2 = $this->discussionThemeHtml(2);
        $discussionTheme3 = $this->discussionThemeHtml(3);

        return <<<HTML
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>経験を次の行動へ</title>
    <style>
        :root { --accent: #cee4d2; }
        body { margin: 0; color: #151515; background: #f1f1f1; font-family: sans-serif; }
        section { min-height: 80vh; padding: 4rem; border-bottom: 1rem solid var(--accent); }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
    </style>
</head>
<body>
    <main class="story">
        <section class="scene">
            <p>PERSONAL LEARNING</p>
            <h1>経験を次の行動へ</h1>
            <p>前回の経験を、新しい実践へつなげるための研修です。</p>
        </section>
        <section class="scene">
            <h2>経験を言語化する</h2>
            <p>うまくいった理由を、再現可能な行動として整理します。</p>
            <ul><li>目的を書く</li><li>前提を確認する</li></ul>
        </section>
        <section class="scene">
            <h2>視点を変える</h2>
            <div class="grid"><p>感覚だけで判断しない。</p><p>根拠を確認して相談する。</p></div>
        </section>
        <section class="scene">
            <h2>次の実践</h2>
            <blockquote>変えるのは結果ではなく、結果につながる行動です。</blockquote>
        </section>
        <section class="scene">
            <h2>実践の振り返り</h2>
            <p>試した行動と結果を記録し、次の改善へつなげます。</p>
        </section>
        <section id="group-discussion" class="scene">
            <h2>グループディスカッション</h2>
            {$discussionTheme1}
            {$discussionTheme2}
            {$discussionTheme3}
        </section>
    </main>
</body>
</html>
HTML;
    }

    private function discussionThemeHtml(int $number): string
    {
        $themes = [
            1 => ['明日から変える行動', '明日から変える行動は何か'],
            2 => ['経験の再現性', '経験を再現可能にするには何が必要か'],
            3 => ['相談のタイミング', '相談するタイミングをどう決めるか'],
        ];
        [$title, $question] = $themes[$number];

        return <<<HTML
<article class="discussion-theme" data-theme-number="{$number}">
    <h3>テーマ{$number}：{$title}</h3>
    <p>過去の経験と次の実践を接続して考えます。</p>
    <p class="discussion-question">{$question}</p>
    <ul><li>{$question}</li></ul>
</article>
HTML;
    }
}
