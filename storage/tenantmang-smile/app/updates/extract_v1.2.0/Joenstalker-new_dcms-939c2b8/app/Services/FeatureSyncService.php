<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Support\Facades\DB;

class FeatureSyncService
{
    /**
     * Synchronizes a tenant's feature overrides array with their active subscription plan.
     * Ensures any newly unlocked components from an upgrade are actively written to the tenant_features 
     * record without destroying manual/beta overrides manually placed by administrators.
     */
    public function syncPlanFeatures(Tenant $tenant): void
    {
        $subscription = $tenant->subscription;

        if (!$subscription || !$subscription->subscription_plan_id) {
            return; // No active plan config to sync from
        }

        // Pull the definitive feature requirements for this tenant's current plan
        $planFeatures = DB::table('plan_features')
            ->where('subscription_plan_id', $subscription->subscription_plan_id)
            ->get();

        foreach ($planFeatures as $pivot) {
            // Evaluates if tenant_features mapping exists natively. 
            // If it doesn't, it creates it, enabling it automatically.
            // If it DOES exist (i.e. manual beta override logic), it skips mutation, effectively 
            // honoring the requirement "DO NOT remove manually enabled features."
            TenantFeature::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'feature_id' => $pivot->feature_id,
            ],
            [
                'is_enabled' => true,
                'override_reason' => 'System Plan Sync'
            ]
            );
        }
    }
}
