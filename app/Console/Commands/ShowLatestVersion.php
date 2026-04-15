<?php

namespace App\Console\Commands;

use App\Services\AppVersionService;
use Illuminate\Console\Command;

class ShowLatestVersion extends Command
{
    protected $signature = 'app:show-latest-version';

    protected $description = 'Show the latest version determined by AppVersionService (GitHub-backed).';

    public function handle()
    {
        try {
            $version = AppVersionService::getVersion();
            $this->info('Latest version (from GitHub or cache): ' . $version);
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to determine latest version: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
