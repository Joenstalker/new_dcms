<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantFeatureUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTenantFeaturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tenantId = $this->tenantId;
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            Log::error("SyncTenantFeaturesJob failed: Tenant [{$tenantId}] not found.");
            return;
        }

        $defaultFeatures = Tenant::getDefaultFeatures();

        $appliedFeatureKeys = TenantFeatureUpdate::where('tenant_id', $tenantId)
            ->where('status', TenantFeatureUpdate::STATUS_APPLIED)
            ->join('features', 'tenant_feature_updates.feature_id', '=', 'features.id')
            ->pluck('features.key')
            ->toArray();

        // Merge defaults with newly applied features
        $allEnabled = array_unique(array_merge($defaultFeatures, $appliedFeatureKeys));

        $tenant->update(['enabled_features' => $allEnabled]);

        Log::info("Synced enabled_features for tenant [{$tenantId}] in background: " . json_encode($allEnabled));
    }
}
