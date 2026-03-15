<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
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
        ]);

        $feature = Feature::create($validated);

        // Automatically assign this feature to all subscription plans with default value
        $this->assignFeatureToAllPlans($feature);

        return redirect()->route('admin.features.index')
            ->with('success', 'Feature created and assigned to all plans.');
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
        ]);

        $feature->update($validated);

        return redirect()->route('admin.features.index')
            ->with('success', 'Feature updated successfully.');
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

        $feature->delete();

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

        return back()->with('success', "Feature assigned to {$plan->name}.");
    }

    /**
     * Remove a feature from a specific plan.
     */
    public function removeFromPlan(Feature $feature, SubscriptionPlan $plan): RedirectResponse
    {
        $plan->features()->detach($feature->id);

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

        return back()->with('success', 'Plan features updated successfully.');
    }

    /**
     * Toggle feature active status.
     */
    public function toggleActive(Feature $feature): RedirectResponse
    {
        $feature->update(['is_active' => !$feature->is_active]);

        $status = $feature->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "Feature {$status} successfully.");
    }
}
