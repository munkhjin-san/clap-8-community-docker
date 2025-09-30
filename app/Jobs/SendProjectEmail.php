<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\ProjectMention;

class SendProjectEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */
    public function __construct(public array $emails, public ProjectMention $mailable) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
         \Mail::to([])->bcc($this->emails)->send($this->mailable);
    }
}
