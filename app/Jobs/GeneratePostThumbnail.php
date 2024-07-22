<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use DateTime;
class GeneratePostThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;
    /**
     * Create a new job instance.
     */
    public function __construct($file)
    {   
        $this->file = $file;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $parentDirectory = dirname($this->file);
        $fileName = pathinfo(basename($this->file), PATHINFO_FILENAME);
      
        $height = 130;
        
        $img = Image::make(storage_path('app/'.$this->file));
        
        $thumbnail = $img->encode('webp')->resize(null, $height, function($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });  
        if (!Storage::disk('local')->exists($parentDirectory . '/thumbnail')) {
            Storage::disk('local')->makeDirectory($parentDirectory . '/thumbnail');
        }
        $thumbnailPath = storage_path('app/') .  $parentDirectory . '/thumbnail/' .  $fileName  . '_thumbnail.webp';
        $thumbnail->save($thumbnailPath, 100);
        $img->destroy();
        $thumbnail->destroy();   
    }
}
