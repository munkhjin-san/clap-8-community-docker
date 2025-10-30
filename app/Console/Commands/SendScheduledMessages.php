<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessMessage;

class SendScheduledMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-scheduled-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ProcessMessage::dispatch();
    }
}
