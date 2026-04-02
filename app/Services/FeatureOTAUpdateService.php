<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\Subscription;
use App\Models\TenantFeatureUpdate;
use App\Mail\NewFeatureUpdateMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class FeatureOTAUpdateService
{
    /**
     * Create feature update records for all eligible tenants
     * (tenants whose plan has this feature enabled)
     */
    public function createUpdateRecordsForEligibleTenants(Feature $feature): int
    {
        // Safety Guard: Don't notify about archived features
        if ($feature->archived_at) {
            return 0;
        }

        $createdCount = 0;
        $tenantIdsToNotify = [];

        // Get all active subscriptions for plans that have this feature
        $subscriptions = Subscription::whereHas('plan.features', function ($query) use ($feature) {
            $query->where('features.id', $feature->id);
        })->where('stripe_status', 'active')
            ->with(['tenant'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $tenantId = $subscription->tenant_id;

            // Check if record already exists
            $exists = TenantFeatureUpdate::where('tenant_id', $tenantId)
                ->where('feature_id', $feature->id)
                ->exists();

            if (!$exists) {
                TenantFeatureUpdate::create([
                    'tenant_id' => $tenantId,
                    'feature_id' => $feature->id,
                    'status' => TenantFeatureUpdate::STATUS_PENDING,
                ]);
                $createdCount++;
                $tenantIdsToNotify[] = $tenantId;
            }
        }

        // Trigger Email notifications for new records
        if (!empty($tenantIdsToNotify)) {
            $this->notifyTenantsViaEmail($feature, $tenantIdsToNotify);
        }

        Log::info("Created {$createdCount} tenant update records for feature: {$feature->name}");

        // Clear cache for all notified tenants
        foreach ($tenantIdsToNotify as $tenantId) {
            Cache::forget("tenant_{$tenantId}_pending_updates_count");
        }

        return $createdCount;
    }

    /**
     * Apply/update feature for a tenant (when they click Update button)
     */
    public function applyUpdate(string $tenantId, array $featureIds): array
    {
        $applied = [];

        foreach ($featureIds as $featureId) {
            $update = TenantFeatureUpdate::where('tenant_id', $tenantId)
                ->where('feature_id', $featureId)
                ->first();

            if ($update && $update->status === TenantFeatureUpdate::STATUS_PENDING) {
                $update->markAsApplied();
                $applied[] = $featureId;
            }
        }

        // Synchronize the tenant's features after applying updates
        if (!empty($applied)) {
            $this->syncTenantFeatures($tenantId);
            Cache::forget("tenant_{$tenantId}_pending_updates_count");
        }

        return $applied;
    }

    /**
     * Synchronize the tenant's enabled_features JSON column with applied OTA updates.
     * This ensures the sidebar and frontend gating reflect the current state.
     */
    public function syncTenantFeatures(string $tenantId): void
    {
        $tenant = \App\Models\Tenant::find($tenantId);
        if (!$tenant)
            return;

        $defaultFeatures = \App\Models\Tenant::getDefaultFeatures();

        $appliedFeatureKeys = TenantFeatureUpdate::where('tenant_id', $tenantId)
            ->where('status', TenantFeatureUpdate::STATUS_APPLIED)
            ->join('features', 'tenant_feature_updates.feature_id', '=', 'features.id')
            ->pluck('features.key')
            ->toArray();

        // Merge defaults with newly applied features
        $allEnabled = array_unique(array_merge($defaultFeatures, $appliedFeatureKeys));

        $tenant->update(['enabled_features' => $allEnabled]);

        Log::info("Synced enabled_features for tenant [{$tenantId}]: " . json_encode($allEnabled));
    }


    /**
     * Get pending updates for a tenant
     */
    public function getPendingUpdates(string $tenantId)
    {
        return TenantFeatureUpdate::where('tenant_id', $tenantId)
            ->pending()
            ->whereHas('feature', function ($query) {
            $query->notArchived()->where('is_active', true);
        })
            ->with('feature')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Notify tenant admins via email about a new feature
     */
    public function notifyTenantsViaEmail(Feature $feature, array $tenantIds): void
    {
        foreach ($tenantIds as $tenantId) {
            $tenant = \App\Models\Tenant::find($tenantId);
            if (!$tenant)
                continue;

            $adminEmail = $tenant->email ?? null;
            if (!$adminEmail)
                continue;

            $admin = \App\Models\User::where('email', $adminEmail)->first();
            if (!$admin)
                continue;

            Mail::to($admin->email)->queue(new NewFeatureUpdateMail($feature, $tenant, $admin));
        }
    }

    /**
     * Push all staged features for a specific Subscription Plan.
     * This notifies ALL tenants in the system asynchronously.
     */
    public function pushPlanUpdates(\App\Models\SubscriptionPlan $plan): \Illuminate\Bus\PendingBatch
    {
        // 1. Get all features attached to the plan that haven't been pushed yet
        $features = $plan->features()->wherePivotNull('pushed_at')->get();
        if ($features->isEmpty()) {
            throw new \Exception("No staged features found for plan [{$plan->name}].");
        }

        $allTenants = \App\Models\Tenant::all();
        $jobs = [];

        foreach ($allTenants as $tenant) {
            $isSubscribedToPlan = Subscription::where('tenant_id', $tenant->id)
                ->where('subscription_plan_id', $plan->id)
                ->where('stripe_status', 'active')
                ->exists();

            $jobs[] = new \App\Jobs\ProcessTenantFeatureUpdateJob(
                $tenant instanceof \App\Models\Tenant ? $tenant : \App\Models\Tenant::find($tenant->id),
                $plan,
                $features->all(),
                !$isSubscribedToPlan
                );
        }

        // 2. Mark features as pushed immediately (so UI reflects "Live" status)
        foreach ($features as $feature) {
            $plan->features()->updateExistingPivot($feature->id, ['pushed_at' => now()]);
        }

        return \Illuminate\Support\Facades\Bus::batch($jobs)
            ->name("Plan Push: {$plan->name}");
    }

    /**
     * Push a specific feature to all eligible tenants asynchronously.
     */
    public function pushFeatureUpdates(Feature $feature): \Illuminate\Bus\PendingBatch
    {
        // 1. Get all active subscriptions for plans that have this feature
        $subscriptions = Subscription::whereHas('plan.features', function ($query) use ($feature) {
            $query->where('features.id', $feature->id);
        })->where('stripe_status', 'active')
            ->with(['tenant', 'plan'])
            ->get();

        $jobs = [];

        foreach ($subscriptions as $subscription) {
            $tenant = $subscription->tenant;
            $jobs[] = new \App\Jobs\ProcessTenantFeatureUpdateJob(
                $tenant instanceof \App\Models\Tenant ? $tenant : \App\Models\Tenant::find($tenant->id),
                $subscription->plan,
            [$feature],
                false // Not an advertisement
                );
        }

        if (empty($jobs)) {
            throw new \Exception("No eligible tenants found for feature [{$feature->name}].");
        }

        return \Illuminate\Support\Facades\Bus::batch($jobs)
            ->name("Feature Push: {$feature->name}");
    }

    /**
     * Push ALL features for a specific plan to a SINGLE tenant.
     * Used when an administrator manually updates a tenant's plan.
     */
    public function pushTenantPlanUpdates(\App\Models\Tenant $tenant, \App\Models\SubscriptionPlan $plan): void
    {
        // 1. Get all features attached to the plan
        $features = $plan->getLoadedFeatures();
        if ($features->isEmpty()) {
            Log::warning("No features found for plan [{$plan->name}] while updating tenant [{$tenant->id}].");
            return;
        }

        // 2. Dispatch a job for this specific tenant
        // We do this sync if it's a single tenant to ensure immediate availability
        \App\Jobs\ProcessTenantFeatureUpdateJob::dispatch(
            $tenant,
            $plan,
            $features->all(),
            false // Not an advertisement
        );

        Log::info("Dispatched single-tenant plan features update for tenant [{$tenant->id}] to plan [{$plan->name}].");
    }
}
