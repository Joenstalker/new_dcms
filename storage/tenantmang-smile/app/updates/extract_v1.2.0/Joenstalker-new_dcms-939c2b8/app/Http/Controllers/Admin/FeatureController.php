<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use App\Services\FeatureOTAUpdateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class FeatureController extends Controller
{
    /**
     * Get plan names that currently have active tenant subscriptions and include this feature.
     */
    private function getBlockingPlanNamesForDeactivation(Feature $feature): array
    {
        return $feature->plans()
            ->whereHas('subscriptions', function ($query) {
                $query->where('stripe_status', 'active')
                    ->where(function ($subQuery) {
                        $subQuery->whereNull('billing_cycle_end')
                            ->orWhere('billing_cycle_end', '>', now());
                    });
            })
            ->select('subscription_plans.name')
            ->distinct()
            ->orderBy('subscription_plans.name')
            ->pluck('subscription_plans.name')
            ->values()
            ->all();
    }

    /**
     * Display a listing of all features.
     */
    public function index(): Response
    {
        $features = Feature::ordered()
            ->notArchived()
            ->where('type', '!=', 'system_version')
            ->with(['plans' => function ($query) {
                $query->whereHas('subscriptions', function ($subscriptionQuery) {
                    $subscriptionQuery->where('stripe_status', 'active')
                        ->where(function ($subQuery) {
                            $subQuery->whereNull('billing_cycle_end')
                                ->orWhere('billing_cycle_end', '>', now());
                        });
                })->select('subscription_plans.id', 'subscription_plans.name');
            }])
            ->get()
            ->map(function (Feature $feature) {
                $feature->blocking_plan_names = $feature->plans
                    ->pluck('name')
                    ->unique()
                    ->sort()
                    ->values();

                return $feature;
            })
            ->groupBy('category');

        $archivedFeatures = Feature::ordered()
            ->archived()
            ->where('type', '!=', 'system_version')
            ->with(['plans' => function ($query) {
                $query->whereHas('subscriptions', function ($subscriptionQuery) {
                    $subscriptionQuery->where('stripe_status', 'active')
                        ->where(function ($subQuery) {
                            $subQuery->whereNull('billing_cycle_end')
                                ->orWhere('billing_cycle_end', '>', now());
                        });
                })->select('subscription_plans.id', 'subscription_plans.name');
            }])
            ->get();

        $plans = SubscriptionPlan::orderBy('price_monthly')->get();

        return Inertia::render('Admin/Features/Index', [
            'features' => $features,
            'archivedFeatures' => $archivedFeatures,
            'plans' => $plans,
        ]);
    }

    /**
     * Archive the specified feature.
     */
    public function archive(Feature $feature): RedirectResponse
    {
        // Safety Guard: Prevent archive while feature is still in plans used by active tenants.
        $blockingPlanNames = $this->getBlockingPlanNamesForDeactivation($feature);
        if (! empty($blockingPlanNames)) {
            $planNames = implode(', ', $blockingPlanNames);

            return back()->with('error', "Cannot archive this feature yet. Remove it from these active plans first so tenants can be notified of the removal: {$planNames}.");
        }

        $feature->update(['archived_at' => now(), 'is_active' => false]);

        \App\Models\AuditLog::record(
            'feature_archived',
            "Archived system feature '{$feature->name}'.",
            'Feature',
            $feature->id
        );

        return back()->with('success', 'Feature archived successfully.');
    }

    /**
     * Restore the specified feature from archive.
     */
    public function restore(Feature $feature): RedirectResponse
    {
        $feature->update(['archived_at' => null, 'is_active' => true]);

        \App\Models\AuditLog::record(
            'feature_restored',
            "Restored system feature '{$feature->name}' from archive.",
            'Feature',
            $feature->id
        );

        return back()->with('success', 'Feature restored from archive.');
    }

    /**
     * Store a newly created feature in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:features,key|regex:/^[a-z_]+$/',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:boolean,numeric,tiered',
            'category' => 'nullable|string|in:core,limits,addons,reports,expansion',
            'options' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'implementation_status' => 'nullable|in:coming_soon,in_development,beta,active,deprecated,maintenance',
            'code_identifier' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
        ]);

        // Set default implementation status
        if (empty($validated['implementation_status'])) {
            $validated['implementation_status'] = Feature::STATUS_COMING_SOON;
        }

        // Set announced_at when creating a new feature
        $validated['announced_at'] = now();

        $feature = Feature::create($validated);

        // Automatically assign this feature to all subscription plans with default value
        $this->assignFeatureToAllPlans($feature);

        // Optionally notify tenants about the new feature
        $notifyTenants = $request->boolean('notify_tenants', false);
        if ($notifyTenants) {
            $otaService = app(FeatureOTAUpdateService::class);
            $count = $otaService->createUpdateRecordsForEligibleTenants($feature);
        }

        \App\Models\AuditLog::record(
            'feature_created',
            "Created system feature '{$feature->name}'.",
            'Feature',
            $feature->id,
        ['key' => $feature->key, 'type' => $feature->type, 'implementation_status' => $feature->implementation_status]
        );

        $message = 'Feature created and assigned to all plans.';
        if ($notifyTenants && isset($count)) {
            $message .= " Notified {$count} tenants.";
        }

        return redirect()->route('admin.features.index')
            ->with('success', $message);
    }

    /**
     * Assign a feature to all subscription plans with default values.
     */
    private function assignFeatureToAllPlans(Feature $feature): void
    {
        $plans = SubscriptionPlan::all();

        foreach ($plans as $plan) {
            // Determine default value based on feature type
            $defaultValue = match ($feature->type) {
                    'boolean' => false,
                    'numeric' => 0,
                    'tiered' => $feature->options ? reset($feature->options) : null,
                    default => null,
                };

            $pivotData = match ($feature->type) {
                    'boolean' => ['value_boolean' => $defaultValue],
                    'numeric' => ['value_numeric' => $defaultValue],
                    'tiered' => ['value_tier' => $defaultValue],
                    default => [],
                };

            $plan->features()->attach($feature->id, $pivotData);
        }
    }

    /**
     * Update the specified feature in storage.
     */
    public function update(Request $request, Feature $feature): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:boolean,numeric,tiered',
            'category' => 'nullable|string|in:core,limits,addons,reports,expansion',
            'options' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'implementation_status' => 'nullable|in:coming_soon,in_development,beta,active,deprecated,maintenance',
            'code_identifier' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
        ]);

        // Safety Guard: Prevent deactivation while feature is still in plans used by active tenants.
        if (array_key_exists('is_active', $validated) && $feature->is_active && ! $validated['is_active']) {
            $blockingPlanNames = $this->getBlockingPlanNamesForDeactivation($feature);
            if (! empty($blockingPlanNames)) {
                $planNames = implode(', ', $blockingPlanNames);

                return back()->with('error', "Cannot deactivate this feature yet. Remove it from these active plans first so tenants can be notified of the removal: {$planNames}.");
            }
        }

        $oldStatus = $feature->implementation_status;
        $newStatus = $validated['implementation_status'] ?? $oldStatus;

        // A status transition back to coming_soon marks a fresh rollout cycle.
        if ($newStatus === Feature::STATUS_COMING_SOON && $oldStatus !== Feature::STATUS_COMING_SOON) {
            $validated['announced_at'] = now();
            $validated['released_at'] = null;
        }

        $feature->update($validated);

        $cycleMessage = null;

        if ($newStatus === Feature::STATUS_COMING_SOON && $oldStatus !== Feature::STATUS_COMING_SOON) {
            $otaService = app(FeatureOTAUpdateService::class);
            $cycle = $otaService->resetUpdateCycleForEligibleTenants($feature);
            $cycleMessage = " New rollout cycle prepared for {$cycle['eligible']} tenants ({$cycle['reset']} reset, {$cycle['created']} created).";
        }

        // If status changed to active, update released_at
        if (isset($validated['implementation_status']) &&
        $validated['implementation_status'] === Feature::STATUS_ACTIVE &&
        $oldStatus !== Feature::STATUS_ACTIVE) {
            $feature->update(['released_at' => now()]);
        }

        // Notify tenants when status changes to active
        $notifyOnStatusChange = $request->boolean('notify_on_status_change', false);
        if ($notifyOnStatusChange && $feature->implementation_status === Feature::STATUS_ACTIVE) {
            $otaService = app(FeatureOTAUpdateService::class);
            $otaService->createUpdateRecordsForEligibleTenants($feature);
        }

        \App\Models\AuditLog::record(
            'feature_updated',
            "Updated system feature '{$feature->name}'.",
            'Feature',
            $feature->id,
        ['changes' => $validated, 'old_status' => $oldStatus, 'new_status' => $feature->implementation_status]
        );

        return redirect()->route('admin.features.index')
            ->with('success', 'Feature updated successfully.'.($cycleMessage ?? ''));
    }

    /**
     * Notify tenants about a new feature update asynchronously.
     */
    public function notifyTenants(Request $request, Feature $feature): array |RedirectResponse
    {
        $otaService = app(FeatureOTAUpdateService::class);

        try {
            $batch = $otaService->pushFeatureUpdates($feature)->dispatch();

            \App\Models\AuditLog::record(
                'feature_notified_async',
                "Triggered async notification for feature '{$feature->name}'.",
                'Feature',
                $feature->id,
            ['batch_id' => $batch->id]
            );

            if ($request->wantsJson()) {
                return [
                    'success' => true,
                    'message' => 'Notification started.',
                    'batch_id' => $batch->id
                ];
            }

            return back()->with('success', "Notification batch started (ID: {$batch->id}).");
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }


    /**
     * Get the status of a specific job batch.
     */
    public function getBatchStatus(string $batchId): array
    {
        $batch = \Illuminate\Support\Facades\Bus::findBatch($batchId);

        if (!$batch) {
            return ['id' => $batchId, 'status' => 'not_found'];
        }

        return [
            'id' => $batch->id,
            'progress' => $batch->progress(),
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'failed_jobs' => $batch->failedJobs,
            'processed_jobs' => $batch->processedJobs(),
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
        ];
    }

    /**
     * Remove the specified feature from storage.
     */
    public function destroy(Feature $feature): RedirectResponse
    {
        // Safety Guard: Must be archived first
        if (!$feature->archived_at) {
            return back()->with('error', 'Feature must be archived before it can be permanently deleted.');
        }

        // Check if feature is assigned to any plans
        if ($feature->plans()->count() > 0) {
            return back()->with('error', 'Cannot delete feature that is assigned to plans. Please remove from all plans first.');
        }

        $featureName = $feature->name;
        $featureId = $feature->id;
        $feature->delete();

        \App\Models\AuditLog::record(
            'feature_deleted',
            "Deleted system feature '{$featureName}'.",
            'Feature',
            $featureId
        );

        return redirect()->route('admin.features.index')
            ->with('success', 'Feature deleted successfully.');
    }

    /**
     * Assign a feature to a specific plan with a value.
     */
    public function assignToPlan(Request $request, Feature $feature): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'value_boolean' => 'nullable|boolean',
            'value_numeric' => 'nullable|integer|min:0',
            'value_tier' => 'nullable|string',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);

        // Determine which value field to use based on feature type
        $pivotData = match ($feature->type) {
                'boolean' => ['value_boolean' => $validated['value_boolean'] ?? false],
                'numeric' => ['value_numeric' => $validated['value_numeric'] ?? 0],
                'tiered' => ['value_tier' => $validated['value_tier'] ?? null],
                default => [],
            };

        $plan->features()->syncWithoutDetaching([
            $feature->id => $pivotData,
        ]);

        \App\Models\AuditLog::record(
            'plan_feature_assigned',
            "Assigned feature '{$feature->name}' to plan '{$plan->name}'.",
            'SubscriptionPlan',
            $plan->id,
        ['feature_id' => $feature->id, 'values' => $pivotData]
        );

        return back()->with('success', "Feature assigned to {$plan->name}.");
    }

    /**
     * Remove a feature from a specific plan.
     */
    public function removeFromPlan(Feature $feature, SubscriptionPlan $plan): RedirectResponse
    {
        $plan->features()->detach($feature->id);

        \App\Models\AuditLog::record(
            'plan_feature_removed',
            "Removed feature '{$feature->name}' from plan '{$plan->name}'.",
            'SubscriptionPlan',
            $plan->id,
        ['feature_id' => $feature->id]
        );

        return back()->with('success', "Feature removed from {$plan->name}.");
    }

    /**
     * Get features for a specific plan (API endpoint).
     */
    public function getPlanFeatures(SubscriptionPlan $plan): Response
    {
        $features = $plan->features()->ordered()->get();

        return Inertia::render('Admin/Features/PlanFeatures', [
            'plan' => $plan,
            'features' => $features,
        ]);
    }

    /**
     * Update feature values for a specific plan.
     */
    public function updatePlanFeatures(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'features' => 'required|array',
            'features.*.feature_id' => 'required|exists:features,id',
            'features.*.value_boolean' => 'nullable|boolean',
            'features.*.value_numeric' => 'nullable|integer|min:0',
            'features.*.value_tier' => 'nullable|string',
        ]);

        foreach ($validated['features'] as $featureData) {
            $feature = Feature::findOrFail($featureData['feature_id']);

            $pivotData = match ($feature->type) {
                    'boolean' => ['value_boolean' => $featureData['value_boolean'] ?? false],
                    'numeric' => ['value_numeric' => $featureData['value_numeric'] ?? 0],
                    'tiered' => ['value_tier' => $featureData['value_tier'] ?? null],
                    default => [],
                };

            $plan->features()->syncWithoutDetaching([
                $feature->id => $pivotData,
            ]);
        }

        \App\Models\AuditLog::record(
            'plan_features_bulk_updated',
            "Updated feature configuration for plan '{$plan->name}'.",
            'SubscriptionPlan',
            $plan->id
        );

        return back()->with('success', 'Plan features updated successfully.');
    }

    /**
     * Toggle feature active status.
     */
    public function toggleActive(Feature $feature): RedirectResponse
    {
        if ($feature->is_active) {
            $blockingPlanNames = $this->getBlockingPlanNamesForDeactivation($feature);
            if (! empty($blockingPlanNames)) {
                $planNames = implode(', ', $blockingPlanNames);

                return back()->with('error', "Cannot deactivate this feature yet. Remove it from these active plans first so tenants can be notified of the removal: {$planNames}.");
            }
        }

        $feature->update(['is_active' => !$feature->is_active]);

        $status = $feature->is_active ? 'enabled' : 'disabled';

        \App\Models\AuditLog::record(
            'feature_toggled',
            "Toggled system feature '{$feature->name}' status to {$status}.",
            'Feature',
            $feature->id,
        ['is_active' => $feature->is_active]
        );

        return back()->with('success', "Feature {$status} successfully.");
    }
}
