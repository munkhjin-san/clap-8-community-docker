<?php

namespace App\Console\Commands;

use App\Jobs\SyncOpenAiFaqRecords;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:dispatch-sync-openai-faq-records')]
#[Description('Dispatch the OpenAI FAQ Markdown vector store sync job')]
class DispatchSyncOpenAiFaqRecords extends Command
{
    public function handle(): int
    {
        SyncOpenAiFaqRecords::dispatch();

        $this->info('OpenAI FAQ Markdown sync job dispatched.');

        return self::SUCCESS;
    }
}
