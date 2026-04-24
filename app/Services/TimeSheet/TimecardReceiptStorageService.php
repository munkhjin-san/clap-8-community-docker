<?php

namespace App\Services\TimeSheet;

use App\Models\TimecardReceiptFile;
use App\Models\timecardCostRecord;
use App\Models\timecardRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TimecardReceiptStorageService
{
    public function storePending(UploadedFile $file, string $draftUuid, int $subjectUserId, ?timecardRecord $timecard = null): TimecardReceiptFile
    {
        $sha256 = hash_file('sha256', $file->getRealPath());
        $uploadedAt = now();
        $extension = $this->extensionFor($file);
        $baseDirectory = sprintf(
            'timecard_files/originals/%s/%s/%s',
            $uploadedAt->format('Y'),
            $uploadedAt->format('m'),
            $subjectUserId
        );
        $fileName = "{$sha256}.{$extension}";
        $canonicalStoragePath = "{$baseDirectory}/{$fileName}";
        $canonicalPath = Str::after($canonicalStoragePath, 'timecard_files/');
        $disk = Storage::disk('local');

        if (!$disk->exists($canonicalStoragePath)) {
            $disk->putFileAs($baseDirectory, $file, $fileName);
        }

        $metadata = $this->fileMetadata($file->getRealPath(), $file->getMimeType());

        return TimecardReceiptFile::create(array_merge([
            'timecard_record_id' => $timecard?->id,
            'draft_uuid' => $draftUuid,
            'user_id' => $subjectUserId,
            'uploaded_by_user_id' => Auth::id(),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $disk->mimeType($canonicalStoragePath) ?: $file->getMimeType(),
            'extension' => $extension,
            'size_bytes' => $disk->size($canonicalStoragePath),
            'sha256' => $sha256,
            'canonical_path' => $canonicalPath,
            'preview_path' => null,
            'status' => 'pending',
            'uploaded_at' => $uploadedAt,
            'metadata' => [
                'storage_path' => $canonicalStoragePath,
                'original_client_mime' => $file->getClientMimeType(),
            ],
        ], $metadata));
    }

    public function finalizeReceipt(
        timecardCostRecord $cost,
        ?int $receiptFileId,
        ?string $filePath,
        ?string $draftUuid,
        array $scanMetadata = []
    ): ?TimecardReceiptFile {
        $receiptFile = $this->findReceiptFile($receiptFileId, $filePath, $draftUuid);
        if (!$receiptFile) {
            return null;
        }

        $receiptFile->fill(array_filter([
            'timecard_record_id' => $cost->record_id,
            'timecard_cost_record_id' => $cost->id,
            'source_type' => $cost->receipt_source_type ?: $receiptFile->source_type,
            'status' => 'finalized',
            'finalized_at' => $receiptFile->finalized_at ?: now(),
            'scan_dpi' => $scanMetadata['scan_dpi'] ?? null,
            'scan_color_depth' => $scanMetadata['scan_color_depth'] ?? null,
            'scan_color_mode' => $scanMetadata['scan_color_mode'] ?? null,
            'document_size' => $scanMetadata['document_size'] ?? null,
            'image_width_px' => $scanMetadata['image_width_px'] ?? null,
            'image_height_px' => $scanMetadata['image_height_px'] ?? null,
        ], fn ($value) => $value !== null));
        $receiptFile->save();

        $cost->forceFill([
            'receipt_file_id' => $receiptFile->id,
            'file_path' => $receiptFile->canonical_path,
            'file_original_name' => $receiptFile->original_name,
            'file_mime_type' => $receiptFile->mime_type,
            'file_size_bytes' => $receiptFile->size_bytes,
            'file_sha256' => $receiptFile->sha256,
            'file_uploaded_at' => $receiptFile->uploaded_at,
            'scan_dpi' => $receiptFile->scan_dpi,
            'scan_color_depth' => $receiptFile->scan_color_depth,
            'scan_color_mode' => $receiptFile->scan_color_mode,
            'document_size' => $receiptFile->document_size,
            'image_width_px' => $receiptFile->image_width_px,
            'image_height_px' => $receiptFile->image_height_px,
        ])->save();

        return $receiptFile;
    }

    public function logicalDelete(?TimecardReceiptFile $receiptFile, ?int $deletedByUserId = null): void
    {
        if (!$receiptFile || $receiptFile->is_deleted) {
            return;
        }

        $receiptFile->fill([
            'is_deleted' => true,
            'deleted_at' => now(),
            'deleted_by_user_id' => $deletedByUserId ?: Auth::id(),
            'status' => 'deleted',
        ])->save();
    }

    public function logicalDeleteByReference(?int $receiptFileId, ?string $filePath, ?string $draftUuid, ?int $deletedByUserId = null): void
    {
        $this->logicalDelete($this->findReceiptFile($receiptFileId, $filePath, $draftUuid), $deletedByUserId);
    }

    public function scanWarnings(TimecardReceiptFile $receiptFile): array
    {
        $warnings = [];
        if ($receiptFile->source_type === 'paper_scan') {
            if ($receiptFile->scan_dpi !== null && $receiptFile->scan_dpi < 200) {
                $warnings[] = 'scan_dpi_below_200';
            }
            if ($receiptFile->scan_color_depth !== null && $receiptFile->scan_color_depth < 24) {
                $warnings[] = 'scan_color_depth_below_24';
            }
            if ($receiptFile->scan_dpi === null && str_starts_with((string) $receiptFile->mime_type, 'image/')) {
                $warnings[] = 'scan_dpi_unavailable';
            }
        }

        return $warnings;
    }

    private function findReceiptFile(?int $receiptFileId, ?string $filePath, ?string $draftUuid): ?TimecardReceiptFile
    {
        if ($receiptFileId) {
            return TimecardReceiptFile::find($receiptFileId);
        }

        if ($filePath) {
            $normalizedPath = Str::after(ltrim($filePath, '/'), 'timecard_files/');
            $query = TimecardReceiptFile::where('canonical_path', $normalizedPath);
            if ($draftUuid) {
                $query->where('draft_uuid', $draftUuid);
            }

            return $query->latest('id')->first();
        }

        return null;
    }

    private function extensionFor(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';

        return substr($extension, 0, 16);
    }

    private function fileMetadata(string $absolutePath, ?string $mimeType): array
    {
        if (!$mimeType || !str_starts_with($mimeType, 'image/')) {
            return [];
        }

        $size = @getimagesize($absolutePath);
        if (!$size) {
            return [];
        }

        return [
            'image_width_px' => $size[0] ?? null,
            'image_height_px' => $size[1] ?? null,
            'scan_color_depth' => isset($size['bits'], $size['channels']) ? (int) $size['bits'] * (int) $size['channels'] : null,
            'scan_color_mode' => isset($size['channels']) && (int) $size['channels'] === 1 ? 'grayscale' : 'color',
        ];
    }
}
