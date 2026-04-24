<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\TimecardReceiptFile;
use App\Models\timecardCostRecord;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RemoveFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $type;
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if($this->type == 'temp')
        {
            $directory = 'temp_upload';

            $maxAgeInDays = 7;

            $thresholdTimestamp = now()->subDays($maxAgeInDays);

            $files = Storage::disk('local')->files($directory);

            foreach ($files as $file) {
                $fileTimestamp = Storage::disk('local')->lastModified($file);

                if ($fileTimestamp <= $thresholdTimestamp->timestamp) {
                    Storage::disk('local')->delete($file);
                }
            }
        } else if ($this->type == 'cost') {
            return;
        } else if ($this->type == 'timecard_orphaned') {
            $directory = 'timecard_files';
            $disk = Storage::disk('local');

            if (!$disk->exists($directory)) {
                return;
            }

            $thresholdTimestamp = now()->subDays(7)->timestamp;
            $attachedFilePaths = timecardCostRecord::withTrashed()
                ->whereNotNull('file_path')
                ->pluck('file_path')
                ->filter()
                ->all();
            $attachedLookup = array_fill_keys($attachedFilePaths, true);
            $receiptFiles = TimecardReceiptFile::query()
                ->where('is_deleted', false)
                ->get(['canonical_path']);
            foreach ($receiptFiles as $receiptFile) {
                $attachedLookup[$receiptFile->canonical_path] = true;
            }

            TimecardReceiptFile::query()
                ->where('status', 'pending')
                ->whereNull('finalized_at')
                ->where('uploaded_at', '<=', now()->subDays(7))
                ->get()
                ->each(function (TimecardReceiptFile $receiptFile) use ($disk): void {
                    $receiptFile->fill([
                        'status' => 'expired',
                        'is_deleted' => true,
                        'deleted_at' => now(),
                    ])->save();

                    $sameCanonicalCount = TimecardReceiptFile::where('canonical_path', $receiptFile->canonical_path)->count();
                    if ($sameCanonicalCount === 1) {
                        $disk->delete("timecard_files/{$receiptFile->canonical_path}");
                    }
                    if ($receiptFile->preview_path) {
                        $disk->delete("timecard_files/{$receiptFile->preview_path}");
                    }
                });

            $files = $disk->allFiles($directory);

            foreach ($files as $file) {
                $relativePath = Str::after($file, "{$directory}/");
                if (str_starts_with($relativePath, 'originals/')) {
                    continue;
                }
                if (str_starts_with($relativePath, 'previews/')) {
                    $disk->delete($file);
                    continue;
                }
                if (isset($attachedLookup[$relativePath])) {
                    continue;
                }

                $fileTimestamp = $disk->lastModified($file);
                if ($fileTimestamp <= $thresholdTimestamp) {
                    $disk->delete($file);
                }
            }
        }
    }
}
