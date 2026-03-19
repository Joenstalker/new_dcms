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
     * Display features page for the current tenant.
     */
    public function features()
    {
        $tenant = tenant();

        // Get the active subscription for this tenant
        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        if (!$subscription || !$subscription->plan) {
            return Inertia::render('Tenant/Settings/Features', [
                'tenant' => $tenant,
                'features' => [],
                'subscription' => null,
            ]);
        }

        // Get features grouped by category
        $featuresByCategory = $subscription->plan->getFeaturesByCategory();

        // Get current subscription data for the frontend
        $subscriptionData = [
            'plan_name' => $subscription->plan->name,
            'billing_cycle' => $subscription->billing_cycle,
            'stripe_status' => $subscription->stripe_status,
            'max_users' => $subscription->plan->getFeatureValue('max_users'),
            'max_patients' => $subscription->plan->getFeatureValue('max_patients'),
            'has_qr_booking' => $subscription->plan->hasFeature('qr_booking'),
            'has_sms' => $subscription->plan->hasFeature('sms_notifications'),
            'has_branding' => $subscription->plan->hasFeature('custom_branding'),
            'has_analytics' => $subscription->plan->hasFeature('advanced_analytics'),
            'has_priority_support' => $subscription->plan->hasFeature('priority_support'),
            'has_multi_branch' => $subscription->plan->hasFeature('multi_branch'),
            'report_level' => $subscription->plan->getFeatureValue('report_level'),
        ];

        return Inertia::render('Tenant/Settings/Features', [
            'tenant' => $tenant,
            'features' => $featuresByCategory,
            'subscription' => $subscriptionData,
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
