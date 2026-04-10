<?php

namespace App\Console\Commands;

use App\Services\TenantUpgradeRolloutService;
use Illuminate\Console\Command;

class RolloutTenantUpgrades extends Command
{
    protected $signature = 'system:rollout-upgrades';

    protected $description = 'Dispatch upgrade jobs for all tenants that are behind the current global version.';

    public function handle(TenantUpgradeRolloutService $rolloutService): int
    {
        $outdated = $rolloutService->getOutdatedTenants();

        if ($outdated->isEmpty()) {
            $this->info('No outdated tenants found. Rollout skipped.');

            return self::SUCCESS;
        }

        $batch = $rolloutService->dispatchRollout($outdated);

        if (! $batch) {
            $this->info('No rollout batch was created.');

            return self::SUCCESS;
        }

        $this->info("Rollout dispatched. Batch ID: {$batch->id}. Tenants queued: {$batch->totalJobs}.");

        return self::SUCCESS;
    }
}
