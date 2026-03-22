<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\FeatureOTAUpdateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $tenant = tenant();
        $bookingUrl = route('tenant.book.create'); // Full booking URL

        // Generate QR Code as Base64 SVG
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)
            ->format('svg')
            ->generate($bookingUrl);

        return Inertia::render('Tenant/Settings/Index', [
            'tenant' => $tenant,
            'booking_url' => $bookingUrl,
            'qr_code' => (string)$qrCode
        ]);
    }

    /**
     * Display a listing of features for the tenant.
     */
    public function features(Request $request)
    {
        $tenant = tenant();
        $subscription = \App\Models\Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->with(['plan.features'])
            ->latest()
            ->first();

        if (!$subscription || !$subscription->plan) {
            return Inertia::render('Tenant/Settings/Features', [
                'features' => [],
            ]);
        }

        // Get pending OTA updates for this tenant
        $otaService = app(\App\Services\FeatureOTAUpdateService::class);
        $pendingUpdates = $otaService->getPendingUpdates($tenant->getTenantKey())
            ->pluck('feature_id')
            ->toArray();

        // Get all active features grouped by category
        $featuresByCategory = \App\Models\Feature::ordered()
            ->active()
            ->get()
            ->groupBy('category')
            ->map(function ($features) use ($subscription, $pendingUpdates) {
                return $features->map(function ($feature) use ($subscription, $pendingUpdates) {
                    $planFeature = $subscription->plan->features->where('id', $feature->id)->first();
                    
                    return [
                        'id' => $feature->id,
                        'name' => $feature->name,
                        'description' => $feature->description,
                        'type' => $feature->type,
                        'category' => $feature->category,
                        'is_enabled' => (bool)$planFeature,
                        'has_pending_update' => in_array($feature->id, $pendingUpdates),
                        'value' => $planFeature ? match($feature->type) {
                            'boolean' => (bool)$planFeature->pivot->value_boolean,
                            'numeric' => (int)$planFeature->pivot->value_numeric,
                            'tiered' => $planFeature->pivot->value_tier,
                            default => null
                        } : null,
                    ];
                });
            });

        return Inertia::render('Tenant/Settings/Features', [
            'features' => $featuresByCategory,
            'has_pending_updates' => count($pendingUpdates) > 0,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'clinic_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'branding_color' => 'nullable|string|max:7',
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:255',
            'about_us_description' => 'nullable|string',
        ]);

        $tenant = tenant();
        if ($tenant) {
            $tenant->update($validated);
        }

        return redirect()->back()->with('success', 'Clinic settings updated successfully.');
    }

    /**
     * Display Updates page for tenant - shows available OTA updates
     */
    public function updates(Request $request)
    {
        $tenant = tenant();

        $otaService = app(FeatureOTAUpdateService::class);
        $pendingUpdates = $otaService->getPendingUpdates($tenant->getTenantKey());

        // Get subscription info
        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        // Format subscription data
        $subscriptionData = null;
        if ($subscription && $subscription->plan) {
            $subscriptionData = [
                'plan_name' => $subscription->plan->name,
                'billing_cycle' => $subscription->billing_cycle,
                'stripe_status' => $subscription->stripe_status,
            ];
        }

        return Inertia::render('Tenant/Settings/Updates', [
            'pending_updates' => $pendingUpdates,
            'subscription' => $subscriptionData,
        ]);
    }

    /**
     * Apply updates (when tenant clicks Update button)
     */
    public function applyUpdates(Request $request)
    {
        $validated = $request->validate([
            'feature_ids' => 'required|array',
            'feature_ids.*' => 'exists:features,id',
        ]);

        $tenant = tenant();
        $otaService = app(FeatureOTAUpdateService::class);

        $applied = $otaService->applyUpdate($tenant->getTenantKey(), $validated['feature_ids']);

        $count = count($applied);

        if ($count > 0) {
            return back()->with('success', "{$count} update(s) applied successfully! You can now test the new features.");
        }

        return back()->with('info', 'No new updates to apply.');
    }

    /**
     * Check for available updates (AJAX endpoint)
     */
    public function checkUpdates(Request $request)
    {
        $tenant = tenant();
        $otaService = app(FeatureOTAUpdateService::class);
        $pendingUpdates = $otaService->getPendingUpdates($tenant->getTenantKey());

        return response()->json([
            'has_updates' => $pendingUpdates->count() > 0,
            'count' => $pendingUpdates->count(),
        ]);
    }
}
