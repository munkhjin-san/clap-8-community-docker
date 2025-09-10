<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CreateDepartureAlert;

class DepartureAlertSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:departure-alert-send';

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
        CreateDepartureAlert::dispatch();
        return 0;
    }
}
