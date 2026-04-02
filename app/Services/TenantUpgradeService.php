<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantUpgradeLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TenantUpgradeService
{
    protected ReleaseService $releaseService;
    protected TenantVersionService $tenantVersionService;
    protected FeatureSyncService $featureSyncService;

    public function __construct(ReleaseService $releaseService, TenantVersionService $tenantVersionService, FeatureSyncService $featureSyncService)
    {
        $this->releaseService = $releaseService;
        $this->tenantVersionService = $tenantVersionService;
        $this->featureSyncService = $featureSyncService;
    }

    /**
     * Upgrades a single tenant strictly within its own context.
     * Transactional rules ensure landlord variables revert on error.
     */
    public function upgrade(Tenant $tenant): bool
    {
        if (!$this->tenantVersionService->needsUpgrade($tenant)) {
            return true;
        }

        // 1. Prevent concurrent upgrades via atomic locking
        $lockKey = "tenant_upgrade_{$tenant->id}";
        $lock = Cache::lock($lockKey, 300); // 5 minute lock prevents race conditions

        if (!$lock->get()) {
            Log::warning("Abort: Tenant {$tenant->id} database upgrade is already running.");
            return false;
        }

        $globalVersion = $this->releaseService->currentVersion();
        $oldVersion = $tenant->version;

        // 2. Safely trigger Tenant Maintenance Mode using soft-cache block.
        // Prevent tenant end-users from corrupting states actively being migrated.
        Cache::put("tenant_maintenance_{$tenant->id}", true, 300);

        // 3. Document payload inside the newly built logs DB table 
        $upgradeLog = TenantUpgradeLog::create([
            'tenant_id' => $tenant->id,
            'from_version' => $oldVersion,
            'to_version' => $globalVersion,
            'status' => 'running',
        ]);

        // Wrap central object mutations in transaction payload
        DB::connection('central')->beginTransaction();

        try {
            // Initiate tenant database context wrapper 
            $tenant->run(function ($tenant) {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);
            });
            $migrationLog = Artisan::output();

            // Synchronize active plan upgrades inside the cycle without losing exceptions
            $this->featureSyncService->syncPlanFeatures($tenant);

            $tenant->version = $globalVersion;
            $tenant->save();

            DB::connection('central')->commit();

            // Lock success states exclusively in raw logs 
            $upgradeLog->update([
                'status' => 'success',
                'log_output' => $migrationLog
            ]);

            Cache::forget("tenant_maintenance_{$tenant->id}");
            $lock->release();

            return true;

        }
        catch (\Exception $e) {
            DB::connection('central')->rollBack();

            // 4. Failure Recovery Strategy & Reversion
            $tenant->version = $oldVersion;

            $upgradeLog->update([
                'status' => 'failed',
                'log_output' => "FATAL MIGRATION EXCEPTION:\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString()
            ]);

            Log::error("Tenant schema fatally corrupted [{$tenant->id}]: " . $e->getMessage());

            // Release environment locks so manual triage isn't blocked by the cron clock 
            Cache::forget("tenant_maintenance_{$tenant->id}");
            $lock->release();

            throw $e;
        }
    }
}
