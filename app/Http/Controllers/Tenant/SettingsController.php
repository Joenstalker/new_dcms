<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\FeatureOTAUpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $subscription = \App\Models\Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->with(['plan.features'])
            ->latest()
            ->first();

        // All available plans for upgrade comparison
        $plans = \App\Models\SubscriptionPlan::with('features')
            ->orderBy('price_monthly')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price_monthly' => (float)$plan->price_monthly,
                    'price_yearly' => (float)$plan->price_yearly,
                    'features' => $plan->getFeaturesByCategory(),
                ];
            });

        return Inertia::render('Tenant/Settings/Index', [
            'days_remaining' => $subscription ? $subscription->days_remaining : null,
            'current_plan_id' => $subscription?->subscription_plan_id,
            'payment_method' => $subscription?->payment_method,
            'stripe_id' => $subscription?->stripe_id,
            'plans' => $plans,
        ]);
    }

    /**
     * Display Custom Branding page.
     */
    public function branding()
    {
        $tenant = tenant();
        $subscription = \App\Models\Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->with(['plan.features'])
            ->latest()
            ->first();

        $staff = \App\Models\User::role(['Dentist', 'Assistant'])->get(['id', 'name']);

        // Booking data (for QRCodeSetup)
        $bookingUrl = route('tenant.book.create');
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)
            ->format('svg')
            ->generate($bookingUrl);

        // Feature data (for FeatureSettings)
        $otaService = app(\App\Services\FeatureOTAUpdateService::class);
        $pendingUpdates = $otaService->getPendingUpdates($tenant->getTenantKey())
            ->pluck('feature_id')
            ->toArray();

        $featuresByCategory = \App\Models\Feature::ordered()
            ->active()
            ->where('key', '!=', 'custom_branding')
            ->get()
            ->groupBy('category')
            ->map(function ($features) use ($subscription, $pendingUpdates) {
                return $features->map(function ($feature) use ($subscription, $pendingUpdates) {
                    $planFeature = $subscription && $subscription->plan ? $subscription->plan->features->where('id', $feature->id)->first() : null;
                    
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

        $branding = \App\Services\TenantBrandingService::getAll();

        return Inertia::render('Tenant/CustomBranding/Index', [
            'tenant' => array_merge($tenant->toArray(), [
                'branding_color' => $branding['primary_color'] ?? $tenant->branding_color,
                'font_family' => $branding['font_family'] ?? $tenant->font_family,
                'portal_config' => $branding['portal_config'] ?? $tenant->portal_config,
                'enabled_features' => $branding['enabled_features'] ?? $tenant->enabled_features ?? \App\Models\Tenant::getDefaultFeatures(),
                'landing_page_config' => $branding['landing_page_config'] ?? $tenant->landing_page_config ?? null,
                'operating_hours' => $branding['operating_hours'] ?? $tenant->getOperatingHoursWithDefaults(),
                'online_booking_enabled' => $branding['online_booking_enabled'] ?? $tenant->isOnlineBookingEnabled(),
                'logo_path' => $branding['logo_base64'] ?? $tenant->logo_path,
                'logo_login_path' => $branding['logo_login_base64'] ?? $tenant->logo_login_path,
                'logo_booking_path' => $branding['logo_booking_base64'] ?? $tenant->logo_booking_path,
                'clinic_name' => $branding['clinic_name'] ?? $tenant->name,
                'clinic_email' => $branding['clinic_email'] ?? $tenant->email,
                'clinic_phone' => $branding['clinic_phone'] ?? $tenant->phone,
                'clinic_address' => $branding['clinic_address'] ?? $tenant->address,
            ]),
            'subscription' => $subscription,
            'is_premium' => $tenant->canCustomizeBranding(),
            'staff' => $staff,
            'booking_url' => $bookingUrl,
            'qr_code' => (string)$qrCode,
            'features' => $featuresByCategory,
            'has_pending_updates' => count($pendingUpdates) > 0,
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

    /**
     * Serve a logo directly from the database binary storage.
     * Memory safe: Streams binary data to the browser.
     */
    public function serveLogo(string $key)
    {
        $tenant = tenant();
        if (!$tenant) abort(404);

        $row = \Illuminate\Support\Facades\DB::table('branding_settings')
            ->where('key', $key)
            ->first();

        if (!$row || $row->binary_value === null) {
            // Fallback to tenant model path if available
            $modelMap = [
                'logo_base64' => 'logo_path',
                'logo_login_base64' => 'logo_login_path',
                'logo_booking_base64' => 'logo_booking_path'
            ];
            
            $modelField = $modelMap[$key] ?? null;
            if ($modelField && $tenant->$modelField) {
                // Return physical file if DB binary is empty
                $path = storage_path('app/public/' . $tenant->$modelField);
                if (file_exists($path)) {
                    return response()->file($path);
                }
            }
            
            abort(404);
        }

        // Detect MIME type from binary header
        $mime = 'image/png'; // default
        $header = substr($row->binary_value, 0, 12);
        if (str_starts_with($header, "\xFF\xD8\xFF")) {
            $mime = 'image/jpeg';
        } elseif (str_starts_with($header, "\x89PNG")) {
            $mime = 'image/png';
        } elseif (str_starts_with($header, 'GIF87a') || str_starts_with($header, 'GIF89a')) {
            $mime = 'image/gif';
        } elseif (str_starts_with($header, 'RIFF') && substr($row->binary_value, 8, 4) === 'WEBP') {
            $mime = 'image/webp';
        } elseif (str_contains($row->binary_value, '<svg') || str_contains($row->binary_value, '<SVG')) {
            $mime = 'image/svg+xml';
        }

        return response($row->binary_value)
            ->header('Content-Type', $mime)
            ->header('Cache-Control', 'public, max-age=31536000');
    }

    public function update(Request $request)
    {
        // Decode JSON strings if sent via multipart/form-data
        foreach (['font_family', 'enabled_features', 'landing_page_config', 'portal_config', 'operating_hours'] as $field) {
            if (is_string($request->input($field)) && (str_starts_with($request->input($field), '[') || str_starts_with($request->input($field), '{'))) {
                $decoded = json_decode($request->input($field), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge([$field => $decoded]);
                }
            }
        }

        $validated = $request->validate([
            'clinic_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'branding_color' => 'nullable|string|max:7',
            'font_family' => 'nullable|array',
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:255',
            'about_us_description' => 'nullable|string',
            'enabled_features' => 'nullable|array',
            'landing_page_config' => 'nullable|array',
            'portal_config' => 'nullable|array',
            'operating_hours' => 'nullable|array',
            'online_booking_enabled' => 'nullable|boolean',
        ]);

        $tenant = tenant();
        if (!$tenant) {
            return redirect()->back()->with('error', 'Tenant not found.');
        }

        // Store branding settings in tenant database
        if (isset($validated['clinic_name'])) \App\Services\TenantBrandingService::set('clinic_name', $validated['clinic_name']);
        if (isset($validated['email'])) \App\Services\TenantBrandingService::set('clinic_email', $validated['email']);
        if (isset($validated['phone'])) \App\Services\TenantBrandingService::set('clinic_phone', $validated['phone']);
        if (isset($validated['address'])) \App\Services\TenantBrandingService::set('clinic_address', $validated['address']);
        
        if (isset($validated['branding_color'])) \App\Services\TenantBrandingService::set('primary_color', $validated['branding_color']);
        if (isset($validated['font_family'])) \App\Services\TenantBrandingService::set('font_family', $validated['font_family']);
        if (isset($validated['enabled_features'])) \App\Services\TenantBrandingService::set('enabled_features', $validated['enabled_features']);
        if (isset($validated['landing_page_config'])) \App\Services\TenantBrandingService::set('landing_page_config', $validated['landing_page_config']);
        if (isset($validated['portal_config'])) \App\Services\TenantBrandingService::set('portal_config', $validated['portal_config']);
        if (isset($validated['operating_hours'])) \App\Services\TenantBrandingService::set('operating_hours', $validated['operating_hours']);
        if (isset($validated['online_booking_enabled'])) \App\Services\TenantBrandingService::set('online_booking_enabled', $validated['online_booking_enabled']);
        
        if (isset($validated['hero_title'])) \App\Services\TenantBrandingService::set('hero_title', $validated['hero_title']);
        if (isset($validated['hero_subtitle'])) \App\Services\TenantBrandingService::set('hero_subtitle', $validated['hero_subtitle']);
        if (isset($validated['about_us_description'])) \App\Services\TenantBrandingService::set('about_us_description', $validated['about_us_description']);

        // Map clinic_name to 'name' and address to 'street' for database synchronization
        if (isset($validated['clinic_name'])) {
            $validated['name'] = $validated['clinic_name'];
        }
        if (isset($validated['address'])) {
            $validated['street'] = $validated['address'];
        }

        // Apply Plan-Based Gating
        if (!$tenant->canCustomizeBranding()) {
            // Basic plan: Only allow clinic_name, email, phone, address
            // Reset other premium fields to standard defaults if provided
            unset(
                $validated['branding_color'], 
                $validated['font_family'], 
                $validated['operating_hours']
            );
        }

        // Explicitly set virtual attributes to ensure they are captured by Stancl's data sync
        // Force save to the data column to ensure persistence
        $data = $tenant->data ?? [];
        if (isset($validated['branding_color'])) $data['branding_color'] = $validated['branding_color'];
        if (isset($validated['font_family'])) $data['font_family'] = $validated['font_family'];
        if (isset($validated['portal_config'])) $data['portal_config'] = $validated['portal_config'];
        if (isset($validated['operating_hours'])) $data['operating_hours'] = $validated['operating_hours'];
        if (array_key_exists('online_booking_enabled', $validated)) $data['online_booking_enabled'] = $validated['online_booking_enabled'];
        
        $tenant->data = $data;
        $tenant->save();

        // Clean up internal keys before update
        unset(
            $validated['clinic_name'], 
            $validated['address']
        );

        // Also update regular columns
        $tenant->update($validated);

        return redirect()->back()->with('success', 'Clinic settings updated successfully.');
    }

    /**
     * Upload a single logo via AJAX (memory-safe, decoupled from main form).
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'field' => 'required|in:logo,logo_login,logo_booking,landing_services,landing_team,landing_contact',
            'image' => 'required|image|mimes:jpeg,png,gif,webp,svg|max:2048',
        ]);

        $tenant = tenant();
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }

        $field = $request->input('field');
        $file = $request->file('image');

        // Validate dimensions
        $dimensions = @getimagesize($file->path());
        if ($dimensions === false) {
            return response()->json(['error' => 'The uploaded file could not be read. Please ensure it is a valid image file.'], 422);
        }
        $width = $dimensions[0];
        $height = $dimensions[1];
        if ($width > 2000 || $height > 2000) {
            return response()->json(['error' => "Image too large ({$width}x{$height}). Maximum allowed resolution is 2000x2000 pixels."], 422);
        }

        $keyMap = [
            'logo' => 'logo_base64',
            'logo_login' => 'logo_login_base64',
            'logo_booking' => 'logo_booking_base64',
            'landing_services' => 'landing_image_services',
            'landing_team' => 'landing_image_team',
            'landing_contact' => 'landing_image_contact',
        ];

        \App\Services\TenantBrandingService::setStreamed($keyMap[$field], $file->path());

        return response()->json([
            'success' => true,
            'url' => route('settings.logo', ['key' => $keyMap[$field]]) . '?v=' . time(),
        ]);
    }

    /**
     * Delete a single logo via AJAX.
     */
    public function deleteLogo(Request $request)
    {
        $request->validate([
            'field' => 'required|in:logo,logo_login,logo_booking,landing_services,landing_team,landing_contact',
        ]);

        $tenant = tenant();
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }

        $field = $request->input('field');
        $keyMap = [
            'logo' => 'logo_base64',
            'logo_login' => 'logo_login_base64',
            'logo_booking' => 'logo_booking_base64',
            'landing_services' => 'landing_image_services',
            'landing_team' => 'landing_image_team',
            'landing_contact' => 'landing_image_contact',
        ];

        try {
            DB::table('branding_settings')->where('key', $keyMap[$field])->delete();
            \App\Services\TenantBrandingService::forget($keyMap[$field]);
        } catch (\Exception $e) {
            // Ignore if row doesn't exist
        }

        return response()->json(['success' => true]);
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
            'feature_ids.*' => 'exists:central.features,id',
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
