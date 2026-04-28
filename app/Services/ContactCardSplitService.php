<?php

namespace App\Services;

use App\Models\ContactBatch;
use App\Models\ContactBatchItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ContactCardSplitService
{
    private const MAX_CARDS_PER_IMAGE = 12;
    private const MIN_BOX_SIDE_RATIO = 0.08;

    /**
     * @param array<int, UploadedFile> $files
     */
    public function createItems(ContactBatch $batch, array $files, string $directory): int
    {
        $index = 0;

        foreach ($files as $file) {
            $boxes = $this->detectCardBoxes($file);

            if (count($boxes) >= 2) {
                $created = $this->createCropItems($batch, $file, $directory, $boxes, $index);
                if ($created > 0) {
                    $index += $created;
                    continue;
                }
            }

            $this->createOriginalItem($batch, $file, $directory, $index);
            $index++;
        }

        return $index;
    }

    /**
     * @return array<int, array{x:int,y:int,width:int,height:int}>
     */
    private function detectCardBoxes(UploadedFile $file): array
    {
        $apiKey = config('services.google.gemini_api_key');
        if (!$apiKey) {
            return [];
        }

        $baseUrl = rtrim(config('services.google.gemini_url') ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $model = config('services.google.contact_card_split_model', 'models/gemini-3-flash-preview');
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $imageData = base64_encode((string) file_get_contents($file->getRealPath()));

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $this->detectionPrompt(),
                        ],
                        [
                            'inlineData' => [
                                'mimeType' => $mime,
                                'data' => $imageData,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'required' => ['x', 'y', 'width', 'height'],
                        'properties' => [
                            'x' => ['type' => 'INTEGER'],
                            'y' => ['type' => 'INTEGER'],
                            'width' => ['type' => 'INTEGER'],
                            'height' => ['type' => 'INTEGER'],
                        ],
                    ],
                ],
                'maxOutputTokens' => 2048,
            ],
        ];

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post("{$baseUrl}/{$model}:generateContent?key={$apiKey}", $payload);
        } catch (\Throwable) {
            return [];
        }

        if ($response->failed()) {
            return [];
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
        if (!is_string($text) || trim($text) === '') {
            return [];
        }

        $decoded = $this->decodeJson($text);
        if (!is_array($decoded)) {
            return [];
        }

        return array_slice($this->normalizeBoxes($decoded), 0, self::MAX_CARDS_PER_IMAGE);
    }

    private function detectionPrompt(): string
    {
        return <<<EOD
            添付画像に写っている名刺だけを検出してください。

            返却はJSON配列のみです。各名刺につき、画像全体を1000x1000に正規化した矩形を返してください。

            [
              {"x": 0, "y": 0, "width": 1000, "height": 500}
            ]

            規則:
            - 名刺ではない背景、机、紙片、影は含めないでください。
            - 名刺が1枚だけの場合は1件だけ返してください。
            - 複数の名刺がある場合は、それぞれ別の矩形として返してください。
            - 最大12件まで返してください。
            - x, y, width, height は0から1000の整数です。
            - 判断できない場合は空配列 [] を返してください。
        EOD;
    }

    /**
     * @param array<int, mixed> $boxes
     * @return array<int, array{x:int,y:int,width:int,height:int}>
     */
    private function normalizeBoxes(array $boxes): array
    {
        $normalized = [];

        foreach ($boxes as $box) {
            if (!is_array($box)) {
                continue;
            }

            $x = (int) ($box['x'] ?? -1);
            $y = (int) ($box['y'] ?? -1);
            $width = (int) ($box['width'] ?? 0);
            $height = (int) ($box['height'] ?? 0);

            if ($x < 0 || $y < 0 || $width <= 0 || $height <= 0) {
                continue;
            }

            $x = max(0, min(1000, $x));
            $y = max(0, min(1000, $y));
            $width = max(1, min(1000 - $x, $width));
            $height = max(1, min(1000 - $y, $height));

            if ($width < self::MIN_BOX_SIDE_RATIO * 1000 || $height < self::MIN_BOX_SIDE_RATIO * 1000) {
                continue;
            }

            $normalized[] = compact('x', 'y', 'width', 'height');
        }

        usort($normalized, fn (array $left, array $right) => [$left['y'], $left['x']] <=> [$right['y'], $right['x']]);

        return $normalized;
    }

    /**
     * @param array<int, array{x:int,y:int,width:int,height:int}> $boxes
     */
    private function createCropItems(ContactBatch $batch, UploadedFile $file, string $directory, array $boxes, int $startIndex): int
    {
        $image = Image::read($file->getRealPath());
        $imageWidth = $image->width();
        $imageHeight = $image->height();
        $created = 0;

        foreach ($boxes as $box) {
            $crop = $this->toPixelBox($box, $imageWidth, $imageHeight);
            if ($crop['width'] < 120 || $crop['height'] < 80) {
                continue;
            }

            $filename = Str::uuid()->toString() . '.webp';
            $relativePath = "{$directory}/{$filename}";
            $absolutePath = Storage::disk('local')->path($relativePath);

            Image::read($file->getRealPath())
                ->crop($crop['width'], $crop['height'], $crop['x'], $crop['y'])
                ->toWebp(90)
                ->save($absolutePath);

            ContactBatchItem::create([
                'contact_batch_id' => $batch->id,
                'index' => $startIndex + $created,
                'original_filename' => $this->cropFilename($file, $created + 1),
                'stored_path' => $relativePath,
                'status' => ContactBatchItem::STATUS_QUEUED,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * @param array{x:int,y:int,width:int,height:int} $box
     * @return array{x:int,y:int,width:int,height:int}
     */
    private function toPixelBox(array $box, int $imageWidth, int $imageHeight): array
    {
        $paddingX = (int) round($box['width'] * 0.02);
        $paddingY = (int) round($box['height'] * 0.02);

        $x = (int) floor(($box['x'] - $paddingX) / 1000 * $imageWidth);
        $y = (int) floor(($box['y'] - $paddingY) / 1000 * $imageHeight);
        $width = (int) ceil(($box['width'] + ($paddingX * 2)) / 1000 * $imageWidth);
        $height = (int) ceil(($box['height'] + ($paddingY * 2)) / 1000 * $imageHeight);

        $x = max(0, min($imageWidth - 1, $x));
        $y = max(0, min($imageHeight - 1, $y));
        $width = max(1, min($imageWidth - $x, $width));
        $height = max(1, min($imageHeight - $y, $height));

        return compact('x', 'y', 'width', 'height');
    }

    private function createOriginalItem(ContactBatch $batch, UploadedFile $file, string $directory, int $index): void
    {
        $filename = Str::uuid()->toString() . '.webp';
        $relativePath = "{$directory}/{$filename}";
        $absolutePath = Storage::disk('local')->path($relativePath);

        try {
            Image::read($file->getRealPath())
                ->toWebp(90)
                ->save($absolutePath);
        } catch (\Throwable) {
            $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg';
            $filename = Str::uuid()->toString() . '.' . $extension;
            $relativePath = $file->storeAs($directory, $filename, 'local');
        }

        ContactBatchItem::create([
            'contact_batch_id' => $batch->id,
            'index' => $index,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $relativePath,
            'status' => ContactBatchItem::STATUS_QUEUED,
        ]);
    }

    private function cropFilename(UploadedFile $file, int $number): string
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'business-card';

        return Str::limit($name, 80, '') . "_card_{$number}.webp";
    }

    private function decodeJson(string $text): mixed
    {
        $clean = trim($text);
        $clean = preg_replace('/^```json\s*/', '', $clean);
        $clean = preg_replace('/```$/', '', $clean);

        return json_decode((string) $clean, true);
    }
}
