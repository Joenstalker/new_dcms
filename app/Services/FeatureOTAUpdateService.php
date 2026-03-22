<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\Subscription;
use App\Models\TenantFeatureUpdate;
use App\Mail\NewFeatureUpdateMail;
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

        return $applied;
    }

    /**
     * Get pending updates for a tenant
     */
    public function getPendingUpdates(string $tenantId)
    {
        return TenantFeatureUpdate::where('tenant_id', $tenantId)
            ->pending()
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
            if (!$tenant) continue;

            $adminEmail = $tenant->email ?? null;
            if (!$adminEmail) continue;

            $admin = \App\Models\User::where('email', $adminEmail)->first();
            if (!$admin) continue;

            Mail::to($admin->email)->queue(new NewFeatureUpdateMail($feature, $tenant, $admin));
        }
    }

    /**
     * Push all staged features for a specific Subscription Plan.
     * This notifies ALL tenants in the system.
     * Subscribers get the updates, non-subscribers get a marketing advertisement.
     */
    public function pushPlanUpdates(\App\Models\SubscriptionPlan $plan): int
    {
        // 1. Get all features attached to the plan that haven't been pushed yet
        $features = $plan->features()->wherePivotNull('pushed_at')->get();
        if ($features->isEmpty()) {
            return 0; // Nothing to push
        }

        $allTenants = \App\Models\Tenant::all();
        $notifiedCount = 0;

        foreach ($allTenants as $tenant) {
            // Determine if the tenant is subscribed to THIS specific plan
            $isSubscribedToPlan = Subscription::where('tenant_id', $tenant->id)
                ->where('subscription_plan_id', $plan->id)
                ->where('stripe_status', 'active')
                ->exists();

            $isAdvertisement = !$isSubscribedToPlan;

            // If they ARE subscribed, they actually get the updates in their portal
            if (!$isAdvertisement) {
                foreach ($features as $feature) {
                    $exists = TenantFeatureUpdate::where('tenant_id', $tenant->id)
                        ->where('feature_id', $feature->id)
                        ->exists();

                    if (!$exists) {
                        TenantFeatureUpdate::create([
                            'tenant_id' => $tenant->id,
                            'feature_id' => $feature->id,
                            'status' => TenantFeatureUpdate::STATUS_PENDING,
                        ]);
                    }
                }
            }

            // Find the administrator User for emailing
            $adminEmail = $tenant->email ?? null;
            if ($adminEmail) {
                $admin = \App\Models\User::where('email', $adminEmail)->first();
                if ($admin) {
                    // Send the consolidated Digest/Advertisement Mail
                    Mail::to($admin->email)->queue(new \App\Mail\PlanFeatureUpdateMail(
                        $plan, 
                        $features->all(), 
                        $tenant, 
                        $admin, 
                        $isAdvertisement
                    ));
                    $notifiedCount++;
                }
            }
        }

        // 2. Mark these features as pushed on the pivot table
        foreach ($features as $feature) {
            $plan->features()->updateExistingPivot($feature->id, ['pushed_at' => now()]);
        }

        Log::info("Pushed updates for plan [{$plan->name}]. Notified {$notifiedCount} tenants about {$features->count()} new features.");
        return $notifiedCount;
    }
}
