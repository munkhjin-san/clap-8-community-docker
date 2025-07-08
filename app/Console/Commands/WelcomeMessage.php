<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\GenerateWelcomeMessage;

class WelcomeMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:welcome-message';

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
        $this->info('Welcome message generation started.');

        // Dispatch the job to generate the welcome message
        GenerateWelcomeMessage::dispatch();

        $this->info('Welcome message generation job dispatched successfully.');
    }
}
