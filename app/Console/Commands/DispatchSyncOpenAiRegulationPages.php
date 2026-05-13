<?php

namespace App\Console\Commands;

use App\Jobs\SyncOpenAiRegulationPages;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:dispatch-sync-openai-regulation-pages')]
#[Description('Dispatch the OpenAI regulation Markdown vector store sync job')]
class DispatchSyncOpenAiRegulationPages extends Command
{
    public function handle(): int
    {
        SyncOpenAiRegulationPages::dispatch();

        $this->info('OpenAI regulation Markdown sync job dispatched.');

        return self::SUCCESS;
    }
}
