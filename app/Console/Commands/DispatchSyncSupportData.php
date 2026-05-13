<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Jobs\SyncSupportData;

#[Signature('app:dispatch-sync-support-data')]
#[Description('Command description')]
class DispatchSyncSupportData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        
        SyncSupportData::dispatch();
    }
}
