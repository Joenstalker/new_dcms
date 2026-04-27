<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantFeatureUpdate;
use App\Services\AppVersionService;
use App\Jobs\UpdateFilesJob;
use App\Jobs\SyncTenantFeaturesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ApplyTenantUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:apply-tenant-update {tenant_id} {feature_ids*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply specific feature updates to a tenant in the background';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Increase limits for background processing
        ini_set('memory_limit', '512M');
        set_time_limit(600); // 10 minutes

        $tenantId = $this->argument('tenant_id');
        $featureIds = $this->argument('feature_ids');

        $this->info("Starting updates for tenant [{$tenantId}]...");

        foreach ($featureIds as $featureId) {
            $update = TenantFeatureUpdate::where('tenant_id', $tenantId)
                ->where('feature_id', $featureId)
                ->with('feature.systemRelease')
                ->first();

            if (!$update) {
                $this->error("Update record not found for feature [{$featureId}]");
                continue;
            }

            if ($update->status !== TenantFeatureUpdate::STATUS_PROCESSING && 
                $update->status !== TenantFeatureUpdate::STATUS_PENDING && 
                $update->status !== TenantFeatureUpdate::STATUS_FAILED) {
                $this->warn("Feature [{$featureId}] is already in status: {$update->status}");
                continue;
            }

            try {
                $feature = $update->feature;
                $this->info("Applying feature: {$feature->name}");

                if ($feature->type === 'system_version' && $feature->system_release_id) {
                    $release = $feature->systemRelease;
                    if ($release) {
                        $tenant = Tenant::find($tenantId);
                        if ($tenant) {
                            $currentVersion = $tenant->version ?: 'v1.0.0';
                            $newVersion = $release->version;

                            $cleanCurrent = ltrim($currentVersion, 'v');
                            $cleanNew = ltrim($newVersion, 'v');

                            if (version_compare($cleanNew, $cleanCurrent, '>')) {
                                $zipUrl = AppVersionService::getDownloadUrl($newVersion);
                                if ($zipUrl) {
                                    $this->info("Updating files to {$newVersion}...");
                                    $tenant->update(['version' => $newVersion]);
                                    
                                    // Since this is already in a background command, 
                                    // we can call the job logic directly or dispatch sync
                                    UpdateFilesJob::dispatch($newVersion, $zipUrl);
                                }
                            }

                            if ($release->requires_db_update) {
                                $this->info("Running migrations...");
                                Artisan::call('tenants:migrate', [
                                    '--tenants' => [$tenantId],
                                ]);
                            }
                        }
                    }
                }

                $update->markAsApplied();
                $this->info("Successfully applied feature [{$featureId}]");

            } catch (\Exception $e) {
                Log::error("Failed to apply update [{$featureId}] for tenant [{$tenantId}]: " . $e->getMessage());
                $update->update([
                    'status' => TenantFeatureUpdate::STATUS_FAILED,
                    'failure_reason' => $e->getMessage()
                ]);
                $this->error("Failed: " . $e->getMessage());
            }
        }

        // Final sync
        $this->info("Syncing features...");
        SyncTenantFeaturesJob::dispatch($tenantId);
        
        $this->info("Update process completed for tenant [{$tenantId}]");
    }
}
