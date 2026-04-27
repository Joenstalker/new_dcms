<?php

namespace App\Services;

use App\Jobs\ProcessTenantFeatureUpdateJob;
use App\Mail\NewFeatureUpdateMail;
use App\Jobs\SyncTenantFeaturesJob;
use App\Jobs\UpdateFilesJob;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantFeatureUpdate;
use App\Models\User;
use App\Services\AppVersionService;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

            if (! $exists) {
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
        if (! empty($tenantIdsToNotify)) {
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
     * Reset the OTA update lifecycle for a feature so tenants can receive it again as a new version.
     *
     * This sets existing eligible tenant records back to pending and creates missing records.
     */
    public function resetUpdateCycleForEligibleTenants(Feature $feature): array
    {
        // Safety Guard: Don't republish archived features
        if ($feature->archived_at) {
            return ['created' => 0, 'reset' => 0, 'eligible' => 0];
        }

        $tenantIds = Subscription::whereHas('plan.features', function ($query) use ($feature) {
            $query->where('features.id', $feature->id);
        })->where('stripe_status', 'active')
            ->pluck('tenant_id')
            ->unique()
            ->values();

        $createdCount = 0;
        $resetCount = 0;

        foreach ($tenantIds as $tenantId) {
            $existing = TenantFeatureUpdate::where('tenant_id', $tenantId)
                ->where('feature_id', $feature->id)
                ->first();

            if (! $existing) {
                TenantFeatureUpdate::create([
                    'tenant_id' => $tenantId,
                    'feature_id' => $feature->id,
                    'status' => TenantFeatureUpdate::STATUS_PENDING,
                ]);
                $createdCount++;
                continue;
            }

            if ($existing->status !== TenantFeatureUpdate::STATUS_PENDING || $existing->applied_at !== null) {
                $existing->update([
                    'status' => TenantFeatureUpdate::STATUS_PENDING,
                    'applied_at' => null,
                ]);
                $resetCount++;
            }
        }

        foreach ($tenantIds as $tenantId) {
            Cache::forget("tenant_{$tenantId}_pending_updates_count");
        }

        Log::info("Reset OTA update cycle for feature [{$feature->key}]", [
            'feature_id' => $feature->id,
            'eligible_tenants' => $tenantIds->count(),
            'created' => $createdCount,
            'reset' => $resetCount,
        ]);

        return [
            'created' => $createdCount,
            'reset' => $resetCount,
            'eligible' => $tenantIds->count(),
        ];
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

            if ($update && ($update->status === TenantFeatureUpdate::STATUS_PENDING || $update->status === TenantFeatureUpdate::STATUS_FAILED)) {
                $update->update(['status' => TenantFeatureUpdate::STATUS_PROCESSING]);
                $applied[] = $featureId;
            }
        }

        if (! empty($applied)) {
            // Truly asynchronous background execution for Windows/Linux
            $idsString = implode(' ', $applied);
            $php = PHP_BINARY;
            $command = "{$php} artisan system:apply-tenant-update {$tenantId} {$idsString}";
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows background command
                pclose(popen("start /B {$command} > nul 2>&1", "r"));
                Log::info("Triggered Windows background update: {$command}");
            } else {
                // Linux/Unix background command
                exec("{$command} > /dev/null 2>&1 &");
                Log::info("Triggered Linux background update: {$command}");
            }

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
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
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

        Log::info("Synced enabled_features for tenant [{$tenantId}]: ".json_encode($allEnabled));
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
            ->with('feature.systemRelease')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Notify tenant admins via email about a new feature
     */
    public function notifyTenantsViaEmail(Feature $feature, array $tenantIds): void
    {
        foreach ($tenantIds as $tenantId) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                continue;
            }

            $adminEmail = $tenant->email ?? null;
            if (! $adminEmail) {
                continue;
            }

            $admin = User::where('email', $adminEmail)->first();
            if (! $admin) {
                continue;
            }

            Mail::to($admin->email)->queue(new NewFeatureUpdateMail($feature, $tenant, $admin));
        }
    }

    /**
     * Push all staged features for a specific Subscription Plan.
     * This notifies ALL tenants in the system asynchronously.
     */
    public function pushPlanUpdates(SubscriptionPlan $plan): PendingBatch
    {
        // 1. Get all features attached to the plan that haven't been pushed yet
        $features = $plan->features()->wherePivotNull('pushed_at')->get();
        if ($features->isEmpty()) {
            throw new \Exception("No staged features found for plan [{$plan->name}].");
        }

        $allTenants = Tenant::all();
        $jobs = [];

        foreach ($allTenants as $tenant) {
            $isSubscribedToPlan = Subscription::where('tenant_id', $tenant->id)
                ->where('subscription_plan_id', $plan->id)
                ->where('stripe_status', 'active')
                ->exists();

            $jobs[] = new ProcessTenantFeatureUpdateJob(
                $tenant instanceof Tenant ? $tenant : Tenant::find($tenant->id),
                $plan,
                $features->all(),
                ! $isSubscribedToPlan
            );
        }

        // 2. Mark features as pushed immediately (so UI reflects "Live" status)
        foreach ($features as $feature) {
            $plan->features()->updateExistingPivot($feature->id, ['pushed_at' => now()]);
        }

        return Bus::batch($jobs)
            ->name("Plan Push: {$plan->name}");
    }

    /**
     * Push a specific feature to all eligible tenants asynchronously.
     */
    public function pushFeatureUpdates(Feature $feature): PendingBatch
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
            if (! $tenant) {
                continue;
            }

            $jobs[] = new ProcessTenantFeatureUpdateJob(
                $tenant instanceof Tenant ? $tenant : Tenant::find($tenant->id),
                $subscription->plan,
                [$feature],
                false // Not an advertisement
            );
        }

        if (empty($jobs)) {
            throw new \Exception("No eligible tenants found for feature [{$feature->name}].");
        }

        return Bus::batch($jobs)
            ->name("Feature Push: {$feature->name}");
    }

    /**
     * Push ALL features for a specific plan to a SINGLE tenant.
     * Used when an administrator manually updates a tenant's plan.
     */
    public function pushTenantPlanUpdates(Tenant $tenant, SubscriptionPlan $plan): void
    {
        // 1. Get all features attached to the plan
        $features = $plan->getLoadedFeatures();
        if ($features->isEmpty()) {
            Log::warning("No features found for plan [{$plan->name}] while updating tenant [{$tenant->id}].");

            return;
        }

        // 2. Dispatch a job for this specific tenant
        // We do this sync if it's a single tenant to ensure immediate availability
        ProcessTenantFeatureUpdateJob::dispatch(
            $tenant,
            $plan,
            $features->all(),
            false // Not an advertisement
        );

        Log::info("Dispatched single-tenant plan features update for tenant [{$tenant->id}] to plan [{$plan->name}].");
    }
}
