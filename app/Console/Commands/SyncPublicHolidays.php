<?php

namespace App\Console\Commands;

use App\Services\PublicHolidaySyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('app:sync-public-holidays {--url= : Override the source CSV URL}')]
#[Description('Sync public holidays from the Cabinet Office CSV into the database')]
class SyncPublicHolidays extends Command
{
    public function __construct(private readonly PublicHolidaySyncService $syncService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $count = $this->syncService->sync($this->option('url') ?: null);

            $this->info("{$count}件の祝日データを同期しました。");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}