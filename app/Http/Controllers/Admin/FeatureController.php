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
     * Display a listing of all features.
     */
    public function index(): Response
    {
        $features = Feature::ordered()
            ->with(['plans' => function ($query) {
            $query->select('subscription_plans.id', 'subscription_plans.name', 'plan_features.value_boolean', 'plan_features.value_numeric', 'plan_features.value_tier');
        }])
            ->get()
            ->groupBy('category');

        $plans = SubscriptionPlan::orderBy('price_monthly')->get();

        return Inertia::render('Admin/Features/Index', [
            'features' => $features,
            'plans' => $plans,
        ]);
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

        $oldStatus = $feature->implementation_status;
        $feature->update($validated);

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
            ->with('success', 'Feature updated successfully.');
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
     * Sync all active features to all eligible tenants (Bulk OTA Sync) asynchronously.
     */
    public function syncAllUpdates(FeatureOTAUpdateService $otaService, Request $request): array |RedirectResponse
    {
        $features = Feature::where('is_active', true)->get();

        if ($features->isEmpty()) {
            return back()->with('error', 'No active features to sync.');
        }

        $jobs = [];

        // Chunk Subscriptions to definitively prevent 1GB memory exhaustion locks natively.
        // We evaluate per-tenant instead of per-feature so we don't duplicate identical Jobs.
        \App\Models\Subscription::where('stripe_status', 'active')
            ->with(['tenant', 'plan.features'])
            ->chunk(100, function ($subscriptions) use (&$jobs, $features) {
            foreach ($subscriptions as $subscription) {
                if (!$subscription->tenant || !$subscription->plan) {
                    continue;
                }

                // Extract what active features this explicit plan has access to
                $planFeatureIds = $subscription->plan->features->pluck('id')->toArray();
                $eligibleFeatures = $features->filter(function ($f) use ($planFeatureIds) {
                            return in_array($f->id, $planFeatureIds);
                        }
                        )->values()->all();

                        // Only construct one single job containing an array of features per tenant payload
                        if (!empty($eligibleFeatures)) {
                            $jobs[] = new \App\Jobs\ProcessTenantFeatureUpdateJob(
                                $subscription->tenant,
                                $subscription->plan,
                                $eligibleFeatures,
                                false
                                );
                        }
                    }
                });

        if (empty($jobs)) {
            return back()->with('error', 'No eligible subscriptions found for any active features.');
        }

        $batch = \Illuminate\Support\Facades\Bus::batch($jobs)
            ->name("Bulk Feature Sync (" . count($jobs) . " updates)")
            ->dispatch();

        \App\Models\AuditLog::record(
            'features_bulk_synced_async',
            "Triggered bulk OTA sync for {$features->count()} active features.",
            'Feature',
            null,
        ['batch_id' => $batch->id, 'total_jobs' => count($jobs)]
        );

        if ($request->wantsJson()) {
            return [
                'success' => true,
                'message' => 'Bulk sync started.',
                'batch_id' => $batch->id
            ];
        }

        return back()->with('success', "Bulk sync batch started (ID: {$batch->id}).");
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
