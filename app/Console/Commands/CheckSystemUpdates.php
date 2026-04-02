<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReleaseService;
use App\Services\AppVersionService;
use Illuminate\Support\Facades\Cache;

class CheckSystemUpdates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'system:check-updates';

    /**
     * The console command description.
     */
    protected $description = 'Checks for global system updates via GitHub and caches availability flags.';

    /**
     * Execute the console command.
     */
    public function handle(ReleaseService $releaseService)
    {
        // Fetch latest raw version natively from cloud/GitHub 
        $githubVersion = AppVersionService::getVersion();

        // Fetch our actively running codebase version
        $localVersion = $releaseService->currentVersion();

        // Safely evaluate strictly for numeric version drifts
        $githubClean = ltrim($githubVersion, 'vV');
        $localClean = ltrim($localVersion, 'vV');

        if (version_compare($githubClean, $localClean, '>')) {
            // New release is globally available but NOT yet pulled down to this server's codebase natively
            Cache::put('global_update_available', true, 3600 * 24);
            $this->info("Update available on registry: {$githubVersion}");
        }
        else {
            Cache::put('global_update_available', false, 3600 * 24);
            $this->info("Landlord ecosystem is globally up to date.");
        }

        // We explicitly DO NOT auto-upgrade any tenants per specifications.
        return Command::SUCCESS;
    }
}
