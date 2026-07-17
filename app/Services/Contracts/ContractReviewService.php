<?php

namespace App\Services\Contracts;

use App\Models\ContractReviewJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenAI;
use Throwable;

class ContractReviewService
{
    public const PENDING_DIR = 'project_files/contracts/pending';

    public function __construct(
        private CachedContractExtractionService $contractExtractionService,
        private GeminiContractOcrService $geminiContractOcrService,
    ) {
    }

    /**
     * Runs a queued contract review to completion. Never throws: failures are
     * recorded on the job row so the sync queue driver can't 500 the request.
     */
    public function execute(ContractReviewJob $job): void
    {
        $job->update([
            'status' => ContractReviewJob::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        try {
            $this->runReview($job);
        } catch (Throwable $exception) {
            report($exception);
            $job->update([
                'status' => ContractReviewJob::STATUS_FAILED,
                'error_message' => Str::limit($exception->getMessage(), 1000),
                'finished_at' => now(),
            ]);
        } finally {
            $this->cleanupPendingFiles($job);
        }
    }

    private function runReview(ContractReviewJob $job): void
    {
        $disk = Storage::disk('local');

        if (!$job->stored_path || !$disk->exists($job->stored_path)) {
            throw new \RuntimeException('レビュー対象の契約ファイルが見つかりません。');
        }

        $absolutePath = $disk->path($job->stored_path);
        $documentInput = [
            'method' => 'openai_file',
            'filename' => $job->original_filename,
            'mime' => $job->mime,
        ];

        if ($job->use_extracted_text) {
            $renderedPagePaths = $job->rendered_page_paths ?? [];

            if ($renderedPagePaths !== []) {
                $absolutePagePaths = array_map(
                    fn (string $path) => $disk->path($path),
                    $renderedPagePaths,
                );
                $documentIndex = $this->contractExtractionService->rememberExtractedIndex(
                    $absolutePath,
                    'pdf',
                    true,
                    function () use ($absolutePagePaths) {
                        $pages = $this->geminiContractOcrService->extractImagePages($absolutePagePaths);

                        return [
                            'document_index' => $this->contractExtractionService->buildIndexFromPages($pages),
                            'extraction' => [
                                'extension' => 'pdf',
                                'method' => 'gemini_rendered_image_ocr',
                                'rendered_pages' => count($absolutePagePaths),
                                'text_length' => self::countDocumentPageTextLength($pages),
                            ],
                        ];
                    }
                );
            } else {
                $documentIndex = $this->contractExtractionService->extractIndex($absolutePath, 'pdf', true);
            }

            $contractText = self::formatDocumentIndexForReview($documentIndex);
            if (trim($contractText) === '') {
                throw new \RuntimeException('PDFのOCR抽出でレビュー可能なテキストを取得できませんでした。');
            }

            $inputContent = [[
                'type' => 'input_text',
                'text' => self::buildExtractedContractReviewInput($job->original_filename, $contractText),
            ]];
            $documentInput = [
                'method' => 'extracted_text',
                'filename' => $job->original_filename,
                'mime' => $job->mime,
                'extraction' => $this->contractExtractionService->lastExtractionMetadata(),
            ];
        } else {
            $base64String = base64_encode((string) file_get_contents($absolutePath));
            $inputContent = [[
                'type' => 'input_file',
                'file_data' => "data:{$job->mime};base64,{$base64String}",
                'filename' => $job->original_filename,
            ]];
        }

        $configKey = $job->review_type === 'deep'
            ? 'services.openai.prompts.legal_deep_review'
            : 'services.openai.prompts.legal_quick_review';
        $promptId = config($configKey);
        if (!$promptId) {
            throw new \RuntimeException('レビュー用プロンプトが設定されていません。');
        }

        $criteria = config('contracts.review_criteria.'.$job->role)
            ?? config('contracts.review_criteria.乙', '');

        $client = OpenAI::client(config('services.openai.api_key'));
        $resp = $client->responses()->create([
            'prompt' => [
                'id' => $promptId,
                'variables' => [
                    'role' => $job->role,
                    'contract_type' => $job->contract_type,
                    'criteria' => $criteria,
                ],
            ],
            'metadata' => [
                'role' => $job->role,
                'contract_type' => $job->contract_type,
            ],
            'input' => [
                [
                    'role' => 'user',
                    'content' => $inputContent,
                ],
            ],
        ]);

        $text = self::extractOutputText($resp);
        $json = json_decode(self::stripJsonCodeFences($text), true);

        if (!is_array($json) || !is_array($json['findings'] ?? null)) {
            Log::warning('Contract review returned an unparseable or schema-less AI response.', [
                'contract_review_job_id' => $job->id,
                'review_type' => $job->review_type,
                'filename' => $job->original_filename,
                'raw_head' => Str::limit((string) $text, 500),
            ]);
            throw new \RuntimeException('AIレビュー結果の解析に失敗しました。時間を置いて再度お試しください。');
        }

        $finalFilePath = null;
        if ($job->review_type !== 'deep' && $this->isPendingPath($job->stored_path)) {
            $finalFilePath = 'project_files/contracts/'.Str::uuid()->toString().'/'.basename($job->original_filename);
            $disk->move($job->stored_path, $finalFilePath);
        }

        $job->update([
            'status' => ContractReviewJob::STATUS_COMPLETED,
            'result_json' => $json,
            'raw_text' => $text,
            'document_input' => $documentInput,
            'file_path' => $finalFilePath,
            'finished_at' => now(),
        ]);
    }

    /**
     * Removes temp inputs under the pending dir. Contract-sourced reviews
     * reference the live contract file, which must never be deleted here.
     */
    private function cleanupPendingFiles(ContractReviewJob $job): void
    {
        $disk = Storage::disk('local');
        $paths = array_merge(
            $this->isPendingPath($job->stored_path) ? [$job->stored_path] : [],
            array_filter($job->rendered_page_paths ?? [], fn ($path) => $this->isPendingPath($path)),
        );

        foreach ($paths as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        if ($this->isPendingPath($job->stored_path)) {
            $pendingDir = dirname($job->stored_path);
            if ($disk->exists($pendingDir) && $disk->files($pendingDir) === [] && $disk->directories($pendingDir) === []) {
                $disk->deleteDirectory($pendingDir);
            }
        }
    }

    private function isPendingPath(?string $path): bool
    {
        return is_string($path) && str_starts_with($path, self::PENDING_DIR.'/');
    }

    public static function extractOutputText(object $response): string
    {
        $text = $response->outputText ?? null;
        if (is_string($text) && trim($text) !== '') {
            return $text;
        }

        $text = '';
        foreach ($response->output ?? [] as $output) {
            if (($output['role'] ?? null) !== 'assistant') {
                continue;
            }

            foreach (($output['content'] ?? []) as $content) {
                $text .= $content['text'] ?? '';
            }
        }

        return $text;
    }

    public static function stripJsonCodeFences(string $text): string
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/', '', $text) ?? $text;
            $text = preg_replace('/\s*```$/', '', $text) ?? $text;
        }

        return $text;
    }

    public static function countDocumentPageTextLength(array $pages): int
    {
        $text = '';

        foreach ($pages as $page) {
            $text .= (string) ($page['text'] ?? '');
        }

        return mb_strlen(trim($text), 'UTF-8');
    }

    public static function formatDocumentIndexForReview(array $documentIndex): string
    {
        $pageTexts = [];

        foreach (($documentIndex['pages'] ?? []) as $page) {
            $text = trim((string) ($page['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $pageNumber = (int) ($page['page'] ?? count($pageTexts) + 1);
            $pageTexts[] = "【Page {$pageNumber}】\n".$text;
        }

        $text = trim(implode("\n\n", $pageTexts));

        return Str::limit($text, 180000, "\n\n[contract text truncated]");
    }

    public static function buildExtractedContractReviewInput(string $fileName, string $contractText): string
    {
        return <<<TXT
The uploaded PDF could not be reviewed reliably as a raw PDF because it has no usable embedded text layer.
The contract text below was extracted with OCR. Review this extracted text as the source contract.

Filename: {$fileName}

{$contractText}
TXT;
    }
}
