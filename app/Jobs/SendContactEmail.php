<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Mail\ContactMention;

class SendContactEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $emails, public ContactMention $mailable) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        \Mail::to([])->bcc($this->emails)->send($this->mailable);
    }
}
