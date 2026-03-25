<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    /**
     * Display a listing of subscription plans.
     */
    public function index(): Response
    {
        $plans = SubscriptionPlan::with('features')->orderBy('price_monthly')->get();
        $allFeatures = \App\Models\Feature::active()->ordered()->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
            'allFeatures' => $allFeatures,
        ]);
    }

    /**
     * Store a newly created subscription plan in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePlan($request);

        $plan = SubscriptionPlan::create($validated);

        AuditLog::record(
            action: 'plan.created',
            description: "Subscription plan '{$plan->name}' created.",
            targetType: 'SubscriptionPlan',
            targetId: (string) $plan->id,
            metadata: ['name' => $plan->name, 'price_monthly' => $plan->price_monthly]
        );

        // Sync to dynamic features
        $plan->syncFeaturesFromLegacy();

        try {
            $this->syncWithStripe($plan);
            return redirect()->route('admin.plans.index')->with('success', 'Plan created and synced with Stripe.');
        } catch (\Exception $e) {
            Log::error('Stripe Sync Error: ' . $e->getMessage());
            return redirect()->route('admin.plans.index')->with('warning', 'Plan saved locally, but Stripe sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified subscription plan in storage.
     */
    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $this->validatePlan($request, $plan->id);

        $plan->update($validated);

        // Sync features if provided
        if ($request->has('features')) {
            foreach ($request->input('features') as $featureData) {
                $feature = \App\Models\Feature::find($featureData['id']);
                if (!$feature) continue;

                if ($featureData['assigned']) {
                    // Plan::addFeature handles pivot logic based on type
                    $plan->addFeature($feature, $featureData['value'] ?? null);
                } else {
                    $plan->removeFeature($feature);
                }
            }
        }

        AuditLog::record(
            action: 'plan.updated',
            description: "Subscription plan '{$plan->name}' updated with usage limits and features.",
            targetType: 'SubscriptionPlan',
            targetId: (string) $plan->id,
            metadata: [
                'name' => $plan->name, 
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'yearly_discount' => $plan->yearly_discount_percent
            ]
        );

        // Sync to dynamic features
        $plan->syncFeaturesFromLegacy();

        try {
            $this->syncWithStripe($plan);
            return redirect()->route('admin.plans.index')->with('success', 'Plan updated and synced with Stripe.');
        } catch (\Exception $e) {
            Log::error('Stripe Sync Error: ' . $e->getMessage());
            return redirect()->route('admin.plans.index')->with('warning', 'Plan updated locally, but Stripe sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified subscription plan from storage.
     */
    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        // Check if there are any active subscriptions for this plan
        if ($plan->subscriptions()->where('stripe_status', 'active')->exists()) {
            return back()->with('error', 'Cannot delete plan with active subscribers.');
        }

        $planName = $plan->name;
        $planId = $plan->id;
        $plan->delete();

        AuditLog::record(
            action: 'plan.deleted',
            description: "Subscription plan '{$planName}' deleted.",
            targetType: 'SubscriptionPlan',
            targetId: (string) $planId
        );

        return redirect()->route('admin.plans.index')->with('success', 'Subscription plan deleted successfully.');
    }

    /**
     * Synchronize the plan with Stripe.
     */
    private function syncWithStripe(SubscriptionPlan $plan): void
    {
        $stripe = new StripeClient(config('cashier.secret'));

        // 1. Handle Product
        if (!$plan->stripe_product_id) {
            $product = $stripe->products->create([
                'name' => 'DCMS - ' . $plan->name,
                'description' => 'Subscription plan for ' . $plan->name,
            ]);
            $plan->stripe_product_id = $product->id;
        }

        // 2. Handle Monthly Price
        // Stripe Prices are immutable. If price changed, we must create a new one.
        $needsNewMonthly = !$plan->stripe_monthly_price_id;

        if ($plan->stripe_monthly_price_id) {
            $stripePrice = $stripe->prices->retrieve($plan->stripe_monthly_price_id);
            if ($stripePrice->unit_amount !== (int)($plan->price_monthly * 100)) {
                $needsNewMonthly = true;
            }
        }

        if ($needsNewMonthly) {
            $price = $stripe->prices->create([
                'unit_amount' => (int)($plan->price_monthly * 100),
                'currency' => 'php',
                'recurring' => ['interval' => 'month'],
                'product' => $plan->stripe_product_id,
            ]);
            $plan->stripe_monthly_price_id = $price->id;
        }

        // 3. Handle Yearly Price
        $needsNewYearly = !$plan->stripe_yearly_price_id;

        if ($plan->stripe_yearly_price_id) {
            $stripePrice = $stripe->prices->retrieve($plan->stripe_yearly_price_id);
            if ($stripePrice->unit_amount !== (int)($plan->price_yearly * 100)) {
                $needsNewYearly = true;
            }
        }

        if ($needsNewYearly) {
            $price = $stripe->prices->create([
                'unit_amount' => (int)($plan->price_yearly * 100),
                'currency' => 'php',
                'recurring' => ['interval' => 'year'],
                'product' => $plan->stripe_product_id,
            ]);
            $plan->stripe_yearly_price_id = $price->id;
        }

        $plan->save();
    }

    /**
     * Force sync a plan with Stripe (Manually triggered).
     */
    public function forceSync(SubscriptionPlan $plan): RedirectResponse
    {
        try {
            $this->syncWithStripe($plan);

            AuditLog::record(
                action: 'plan.synced',
                description: "Manually synchronized plan '{$plan->name}' with Stripe.",
                targetType: 'SubscriptionPlan',
                targetId: (string) $plan->id
            );

            return back()->with('success', 'Plan synchronized with Stripe successfully.');
        }
        catch (\Exception $e) {
            Log::error('Stripe Force Sync Error: ' . $e->getMessage());
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Push staged OTA features to all tenants (Updates & Advertisements).
     */
    public function pushUpdates(SubscriptionPlan $plan, \App\Services\FeatureOTAUpdateService $otaService): RedirectResponse
    {
        try {
            $batch = $otaService->pushPlanUpdates($plan)->dispatch();
            $notifiedCount = $batch->totalJobs;

            AuditLog::record(
                action: 'plan.features_pushed',
                description: "Pushed updates for plan '{$plan->name}' to $notifiedCount tenants.",
                targetType: 'SubscriptionPlan',
                targetId: (string) $plan->id,
                metadata: ['name' => $plan->name, 'job_id' => $batch->id]
            );

            return back()->with('success', "Updates pushed successfully! {$notifiedCount} tenants notified (Job ID: {$batch->id}).");
        } catch (\Exception $e) {
            Log::error('OTA Push Error: ' . $e->getMessage());
            return back()->with('error', 'Push failed: ' . $e->getMessage());
        }
    }

    /**
     * Push updates to multiple plans at once.
     */
    public function batchPushUpdates(Request $request, \App\Services\FeatureOTAUpdateService $otaService): RedirectResponse
    {
        $planIds = $request->input('plan_ids', []);
        
        if (empty($planIds)) {
            return back()->with('error', 'No plans selected for update.');
        }

        $plans = SubscriptionPlan::whereIn('id', $planIds)->get();
        $totalNotified = 0;
        $jobIds = [];

        try {
            foreach ($plans as $plan) {
                // Check if plan has staged features
                $stagedCount = $plan->features()->wherePivotNull('pushed_at')->count();
                if ($stagedCount === 0) continue;

                $batch = $otaService->pushPlanUpdates($plan)->dispatch();
                $totalNotified += $batch->totalJobs;
                $jobIds[] = $batch->id;

                AuditLog::record(
                    action: 'plan.features_pushed_batch',
                    description: "Batch pushed updates for plan '{$plan->name}' as part of a multi-plan push.",
                    targetType: 'SubscriptionPlan',
                    targetId: (string) $plan->id,
                    metadata: ['name' => $plan->name, 'job_id' => $batch->id]
                );
            }

            if (empty($jobIds)) {
                return back()->with('info', 'No staged features found to push.');
            }

            return back()->with('success', "Started batch update for " . count($jobIds) . " plans. Total notifications queued: {$totalNotified}.");
        } catch (\Exception $e) {
            Log::error('Batch OTA Push Error: ' . $e->getMessage());
            return back()->with('error', 'Batch push failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate the subscription plan request.
     */
    protected function validatePlan(Request $request, $id = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:subscription_plans,name' . ($id ? ",$id" : ""),
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'yearly_discount_percent' => 'nullable|numeric|min:0|max:100',
            'max_users' => 'required|integer|min:1',
            'max_patients' => 'nullable|integer|min:0',
            'max_appointments' => 'nullable|integer|min:0',
            'has_qr_booking' => 'boolean',
            'has_sms' => 'boolean',
            'has_branding' => 'boolean',
            'has_analytics' => 'boolean',
            'has_priority_support' => 'boolean',
            'has_multi_branch' => 'boolean',
            'report_level' => 'required|string|in:basic,enhanced,advanced',
            'max_storage_mb' => 'nullable|integer|min:0',
        ]);

        // Transform 0 to null (unlimited) for specific input fields
        $validated['max_patients'] = ((isset($validated['max_patients']) && $validated['max_patients'] === 0) || !isset($validated['max_patients'])) ? null : $validated['max_patients'];
        $validated['max_appointments'] = ((isset($validated['max_appointments']) && $validated['max_appointments'] === 0) || !isset($validated['max_appointments'])) ? null : $validated['max_appointments'];

        return $validated;
    }
}
