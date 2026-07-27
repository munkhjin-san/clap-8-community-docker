<?php

namespace Tests\Unit\Services\Support;

use App\Services\Support\OpenAiFileSearchResponseParser;
use PHPUnit\Framework\TestCase;

class OpenAiFileSearchResponseParserTest extends TestCase
{
    public function test_it_extracts_answer_queries_and_page_references(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'file_search_call',
                    'queries' => ['有給休暇 取得要件', '有給休暇 取得要件'],
                    'results' => [
                        [
                            'filename' => '就業規則__p012.md',
                            'attributes' => [
                                'original_file_name' => '就業規則.pdf',
                                'page' => 12,
                            ],
                        ],
                        [
                            'filename' => '賃金規程__p003.md',
                            'attributes' => [],
                        ],
                        [
                            'filename' => 'ログインできません.md',
                            'attributes' => [
                                'question_and_answer_record_id' => 42,
                                'question' => 'ログインできません',
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'message',
                    'content' => [
                        ['type' => 'output_text', 'text' => '取得要件は次のとおりです。'],
                    ],
                ],
            ],
        ];

        $parsed = (new OpenAiFileSearchResponseParser())->parse($response);

        $this->assertSame('取得要件は次のとおりです。', $parsed['reply']);
        $this->assertSame(['有給休暇 取得要件'], $parsed['keywords']);
        $this->assertSame(
            ['就業規則.pdf p.12', '賃金規程.pdf p.3', 'FAQ「ログインできません」'],
            $parsed['sources'],
        );
    }

    public function test_it_ignores_malformed_results_and_concatenates_text_parts(): void
    {
        $parsed = (new OpenAiFileSearchResponseParser())->parse([
            'output' => [
                [
                    'type' => 'file_search_call',
                    'results' => [['filename' => null]],
                ],
                [
                    'type' => 'message',
                    'content' => [
                        ['type' => 'output_text', 'text' => '前半'],
                        ['type' => 'refusal', 'refusal' => 'ignored'],
                        ['type' => 'output_text', 'text' => '後半'],
                    ],
                ],
            ],
        ]);

        $this->assertSame('前半後半', $parsed['reply']);
        $this->assertSame([], $parsed['sources']);
        $this->assertSame([], $parsed['keywords']);
    }
}
