<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\FeatureOTAUpdateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Tenant Settings Controller
 * 
 * Handles settings for the dental clinic (tenant).
 */
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

        $staff = \App\Models\User::role(['Dentist', 'Assistant'])->get(['id', 'name']);

        return Inertia::render('Tenant/Settings/Index', [
            'tenant' => $tenant,
            'is_premium' => $tenant->canCustomizeBranding(),
            'booking_url' => $bookingUrl,
            'qr_code' => (string)$qrCode,
            'staff' => $staff
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
            'font_family' => 'nullable|string|max:50',
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:255',
            'about_us_description' => 'nullable|string',
            'enabled_features' => 'nullable|array',
            'landing_page_config' => 'nullable|string', // JSON string from frontend
            'logo' => 'nullable|image|max:2048',
            'logo_login' => 'nullable|image|max:2048',
            'logo_booking' => 'nullable|image|max:2048',
        ]);

        $tenant = tenant();
        if (!$tenant) {
            return redirect()->back()->with('error', 'Tenant not found.');
        }

        // Handle JSON decoding for landing_page_config if it's sent as a string
        if (isset($validated['landing_page_config']) && is_string($validated['landing_page_config'])) {
            $validated['landing_page_config'] = json_decode($validated['landing_page_config'], true);
        }

        // Handle Logo Uploads
        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('branding', 'public');
        }
        if ($request->hasFile('logo_login')) {
            $validated['logo_login_path'] = $request->file('logo_login')->store('branding', 'public');
        }
        if ($request->hasFile('logo_booking')) {
            $validated['logo_booking_path'] = $request->file('logo_booking')->store('branding', 'public');
        }

        // Map clinic_name to 'name' and address to 'street' for database synchronization
        if (isset($validated['clinic_name'])) {
            $validated['name'] = $validated['clinic_name'];
        }
        if (isset($validated['address'])) {
            $validated['street'] = $validated['address'];
        }

        // Apply Plan-Based Gating
        if (!$tenant->canCustomizeBranding()) {
            // Basic plan: Only allow logo (Landing Header), clinic_name, email, phone, address
            // Reset other premium fields to standard defaults if provided
            unset(
                $validated['branding_color'], 
                $validated['font_family'], 
                $validated['logo_login_path'], 
                $validated['logo_booking_path'],
                $validated['landing_page_config']
            );
        }

        // Clean up internal keys before update
        unset(
            $validated['logo'], 
            $validated['logo_login'], 
            $validated['logo_booking'], 
            $validated['clinic_name'], 
            $validated['address']
        );

        $tenant->update($validated);

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
