<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $plans = SubscriptionPlan::orderBy('price_monthly')->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans
        ]);
    }

    /**
     * Store a newly created subscription plan in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePlan($request);

        $plan = SubscriptionPlan::create($validated);

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

        $plan->delete();

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
        // In Stripe, prices are immutable. If price changes, we create a new one.
        // For simplicity, we create one if it doesn't exist.
        if (!$plan->stripe_monthly_price_id) {
            $price = $stripe->prices->create([
                'unit_amount' => $plan->price_monthly * 100, // cents
                'currency' => 'php',
                'recurring' => ['interval' => 'month'],
                'product' => $plan->stripe_product_id,
            ]);
            $plan->stripe_monthly_price_id = $price->id;
        }

        // 3. Handle Yearly Price
        if (!$plan->stripe_yearly_price_id) {
            $price = $stripe->prices->create([
                'unit_amount' => $plan->price_yearly * 100, // cents
                'currency' => 'php',
                'recurring' => ['interval' => 'year'],
                'product' => $plan->stripe_product_id,
            ]);
            $plan->stripe_yearly_price_id = $price->id;
        }

        $plan->save();
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
        ]);

        // Transform 0 to null (unlimited) for specific input fields
        $validated['max_patients'] = ((isset($validated['max_patients']) && $validated['max_patients'] === 0) || !isset($validated['max_patients'])) ? null : $validated['max_patients'];
        $validated['max_appointments'] = ((isset($validated['max_appointments']) && $validated['max_appointments'] === 0) || !isset($validated['max_appointments'])) ? null : $validated['max_appointments'];

        return $validated;
    }
}
