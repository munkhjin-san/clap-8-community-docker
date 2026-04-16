<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\timecardCostRecord;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

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
            $line = Carbon::now()->subMonth()->format('Y:m:d H:i:s');
            $unused_files = timecardCostRecord::where('deleted_at', '<=', $line)->onlyTrashed()->get();
            foreach($unused_files as $file){           
                Storage::disk('local')->delete("timecard_files/{$file->file_path}");        
            }
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
            $files = $disk->files($directory);

            foreach ($files as $file) {
                $relativePath = File::basename($file);
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
