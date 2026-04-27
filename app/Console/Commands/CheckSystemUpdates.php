<?php

namespace App\Console\Commands;

use App\Models\Feature;
use App\Models\SystemRelease;
use App\Models\Tenant;
use App\Models\TenantFeatureUpdate;
use App\Services\AppVersionService;
use App\Services\FeatureOTAUpdateService;
use App\Services\ReleaseService;
use App\Services\TenantUpgradeRolloutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
    public function handle(ReleaseService $releaseService, FeatureOTAUpdateService $otaService)
    {
        $this->info('Checking for system updates from GitHub...');

        $releases = AppVersionService::getReleaseHistory();

        if (empty($releases)) {
            $this->error('No releases found on GitHub registry.');

            return Command::FAILURE;
        }

        $newCount = 0;

        foreach ($releases as $ghRelease) {
            $version = $ghRelease['tag_name'];
            $cleanVersion = ltrim($version, 'vV');

            // 1. Check if SystemRelease exists
            $systemRelease = SystemRelease::where('version', $version)->first();

            if (! $systemRelease) {
                $this->info("New release found: {$version}");

                // Detect migration requirement
                $requiresDb = str_contains(strtoupper($ghRelease['body'] ?? ''), '[MIGRATION]');

                // Use ReleaseService to register the release (ensures cache is cleared)
                $systemRelease = $releaseService->registerRelease(
                    $version,
                    $ghRelease['body'] ?? null,
                    false,
                    $requiresDb
                );

                // 2. Create/Update a Feature record for this version
                $featureKey = 'system_version_'.str_replace('.', '_', $cleanVersion);
                $feature = Feature::firstOrCreate(
                    ['key' => $featureKey],
                    [
                        'name' => "System Update: {$version}",
                        'description' => 'Official platform update released on GitHub.',
                        'type' => 'system_version',
                        'category' => 'expansion',
                        'is_active' => true,
                        'implementation_status' => Feature::STATUS_ACTIVE,
                        'system_release_id' => $systemRelease->id,
                        'released_at' => $systemRelease->released_at,
                    ]
                );

                // 3. Broadcast to all active tenants
                $this->broadcastSystemUpdate($otaService, $feature);
                $newCount++;
            }
        }

        if ($newCount > 0) {
            $this->info("Successfully synced {$newCount} new releases.");
            Cache::put('global_update_available', true, 3600 * 24);

            /** @var TenantUpgradeRolloutService $rolloutService */
            $rolloutService = app(TenantUpgradeRolloutService::class);
            if ($rolloutService->isAutoRolloutEnabled()) {
                $this->info('Auto rollout enabled. Dispatching tenant upgrade rollout batch...');
                Artisan::call('system:rollout-upgrades');
                Log::info('Automatic tenant upgrade rollout triggered by system:check-updates command.');
            }
        } else {
            $this->info('System is already synchronized with GitHub releases.');
            Cache::put('global_update_available', false, 3600 * 24);
        }

        return Command::SUCCESS;
    }

    /**
     * Explicitly notify all active tenants about a system-level update
     */
    protected function broadcastSystemUpdate($otaService, $feature)
    {
        $feature->loadMissing('systemRelease');
        $releaseVersion = ltrim((string) optional($feature->systemRelease)->version, 'vV');

        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            $tenantVersion = ltrim((string) ($tenant->version ?? 'v1.0.0'), 'vV');

            // Skip creating pending updates for tenants already on this version or newer.
            if ($releaseVersion !== '' && version_compare($releaseVersion, $tenantVersion, '<=')) {
                continue;
            }

            // Check if record already exists
            $exists = TenantFeatureUpdate::where('tenant_id', $tenant->id)
                ->where('feature_id', $feature->id)
                ->exists();

            if (! $exists) {
                TenantFeatureUpdate::create([
                    'tenant_id' => $tenant->id,
                    'feature_id' => $feature->id,
                    'status' => TenantFeatureUpdate::STATUS_PENDING,
                ]);
                Cache::forget("tenant_{$tenant->id}_pending_updates_count");
            }
        }
    }
}
