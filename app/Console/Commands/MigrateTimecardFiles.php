<?php

namespace App\Console\Commands;

use App\Models\TimecardAuditEvent;
use App\Models\TimecardAuditEventProjection;
use App\Models\TimecardCostOcrRun;
use App\Models\TimecardReceiptFile;
use App\Models\timecardCostRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateTimecardFiles extends Command
{
    protected $signature = 'app:migrate-timecard-files
        {--execute : Move files and update database records}
        {--limit= : Limit the number of cost records processed}';

    protected $description = 'Move flat timecard files into year/month/user folders and update path references';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $disk = Storage::disk('local');
        $processed = 0;
        $migrated = 0;
        $missing = 0;
        $skipped = 0;

        if (!$execute) {
            $this->warn('Dry run only. Re-run with --execute to move files and update records.');
        }

        $query = timecardCostRecord::withTrashed()
            ->with('timecard:id,day')
            ->whereNotNull('file_path')
            ->where('file_path', 'not like', 'originals/%')
            ->orderBy('id');

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $query->get()->each(function (timecardCostRecord $cost) use (
            $disk,
            $execute,
            &$processed,
            &$migrated,
            &$missing,
            &$skipped
        ): void {
            $processed++;
            $oldRelativePath = $this->normalizeTimecardPath((string) $cost->file_path);

            if ($oldRelativePath === '') {
                $skipped++;
                return;
            }

            $oldStoragePath = "timecard_files/{$oldRelativePath}";
            if (!$disk->exists($oldStoragePath)) {
                $recoveredPath = $this->findMovedPathByBasename($disk, $oldRelativePath);
                if ($recoveredPath) {
                    $oldStoragePath = $recoveredPath;
                    $this->warn("Recovering moved file for cost {$cost->id}: {$recoveredPath}");
                }
            }

            if (!$disk->exists($oldStoragePath)) {
                $missing++;
                $this->warn("Missing file for cost {$cost->id}: timecard_files/{$oldRelativePath}");
                return;
            }

            $sha256 = hash_file('sha256', $disk->path($oldStoragePath));
            $newRelativePath = $this->newRelativePath($cost, $oldRelativePath, $sha256);
            $newStoragePath = "timecard_files/{$newRelativePath}";

            $newRelativePath = Str::after($newStoragePath, 'timecard_files/');

            $this->line("{$oldRelativePath} -> {$newRelativePath}");

            if (!$execute) {
                $migrated++;
                return;
            }

            DB::transaction(function () use ($cost, $disk, $oldRelativePath, $oldStoragePath, $newRelativePath, $newStoragePath, $sha256): void {
                $disk->makeDirectory(dirname($newStoragePath));
                if (!$disk->exists($newStoragePath)) {
                    $disk->copy($oldStoragePath, $newStoragePath);
                }
                $this->updatePathReferences($cost, $oldRelativePath, $newRelativePath, $sha256, $newStoragePath);
            });

            $disk->delete($oldStoragePath);
            $migrated++;
        });

        $this->info("Processed: {$processed}. Migrated: {$migrated}. Missing: {$missing}. Skipped: {$skipped}.");

        return self::SUCCESS;
    }

    private function normalizeTimecardPath(string $path): string
    {
        $path = ltrim($path, '/');

        return Str::after($path, 'timecard_files/');
    }

    private function newRelativePath(timecardCostRecord $cost, string $fileName, string $sha256): string
    {
        $date = $cost->timecard?->day
            ?? ($cost->date_month ? "{$cost->date_month}-01" : null)
            ?? $cost->file_uploaded_at
            ?? $cost->created_at
            ?? now();
        $carbon = Carbon::parse($date);
        $userId = (int) ($cost->user_id ?: 0);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'bin';

        return "originals/{$carbon->format('Y')}/{$carbon->format('m')}/{$userId}/{$sha256}.{$extension}";
    }

    private function findMovedPathByBasename($disk, string $fileName): ?string
    {
        foreach ($disk->allFiles('timecard_files') as $path) {
            if (basename($path) === basename($fileName)) {
                return $path;
            }
        }

        return null;
    }

    private function updatePathReferences(timecardCostRecord $cost, string $oldPath, string $newPath, string $sha256, string $storagePath): TimecardReceiptFile
    {
        $receiptFile = TimecardReceiptFile::updateOrCreate(
            ['timecard_cost_record_id' => $cost->id],
            [
                'timecard_record_id' => $cost->record_id,
                'draft_uuid' => $cost->draft_uuid,
                'user_id' => $cost->user_id,
                'uploaded_by_user_id' => null,
                'original_name' => $cost->file_original_name ?: basename($oldPath),
                'mime_type' => $cost->file_mime_type ?: Storage::disk('local')->mimeType($storagePath),
                'extension' => pathinfo($newPath, PATHINFO_EXTENSION),
                'size_bytes' => Storage::disk('local')->size($storagePath),
                'sha256' => $sha256,
                'canonical_path' => $newPath,
                'source_type' => $cost->receipt_source_type ?: 'paper_scan',
                'status' => $cost->trashed() ? 'deleted' : 'finalized',
                'uploaded_at' => $cost->file_uploaded_at ?? $cost->created_at ?? now(),
                'finalized_at' => $cost->deleted_at ? null : ($cost->updated_at ?? now()),
                'scan_dpi' => $cost->scan_dpi,
                'scan_color_depth' => $cost->scan_color_depth,
                'scan_color_mode' => $cost->scan_color_mode,
                'document_size' => $cost->document_size,
                'image_width_px' => $cost->image_width_px,
                'image_height_px' => $cost->image_height_px,
                'is_deleted' => $cost->trashed(),
                'deleted_at' => $cost->deleted_at,
                'metadata' => ['migrated_from' => $oldPath],
            ]
        );

        $cost->forceFill([
            'receipt_file_id' => $receiptFile->id,
            'file_path' => $newPath,
            'file_sha256' => $sha256,
            'file_size_bytes' => $receiptFile->size_bytes,
            'file_mime_type' => $receiptFile->mime_type,
        ])->save();

        TimecardCostOcrRun::query()
            ->where('source_file_path', $oldPath)
            ->update(['source_file_path' => $newPath]);

        TimecardAuditEventProjection::query()
            ->where('file_path', $oldPath)
            ->update([
                'file_path' => $newPath,
                'receipt_file_id' => $receiptFile->id,
                'file_sha256' => $sha256,
            ]);

        TimecardAuditEvent::query()
            ->where(function ($query) use ($oldPath) {
                $query->where('before_state->file_path', $oldPath)
                    ->orWhere('after_state->file_path', $oldPath)
                    ->orWhere('metadata->file_path', $oldPath);
            })
            ->each(function (TimecardAuditEvent $event) use ($oldPath, $newPath): void {
                $event->before_state = $this->replaceFilePath($event->before_state, $oldPath, $newPath);
                $event->after_state = $this->replaceFilePath($event->after_state, $oldPath, $newPath);
                $event->metadata = $this->replaceFilePath($event->metadata, $oldPath, $newPath);
                $event->save();
            });

        return $receiptFile;
    }

    private function replaceFilePath(?array $state, string $oldPath, string $newPath): ?array
    {
        if ($state === null) {
            return null;
        }

        if (($state['file_path'] ?? null) === $oldPath) {
            $state['file_path'] = $newPath;
        }

        return $state;
    }
}
