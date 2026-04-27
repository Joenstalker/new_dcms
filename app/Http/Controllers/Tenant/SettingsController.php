<?php

namespace App\Http\Controllers\Tenant;

use App\Events\OnlineBookingStatusUpdated;
use App\Events\TenantBrandingUpdated;
use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SystemRelease;
use App\Models\SystemSetting;
use App\Models\TenantFeatureUpdate;
use App\Models\TenantPaymentHistory;
use App\Models\User;
use App\Services\AppVersionService;
use App\Services\FeatureOTAUpdateService;
use App\Services\ReleaseService;
use App\Services\TenantBrandingService;
use App\Services\TenantEffectiveLimitService;
use App\Services\TenantFeatureGateService;
use App\Services\TenantLimitOverageService;
use App\Services\TenantSecuritySettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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

        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->with(['plan.features'])
            ->latest()
            ->first();

        $usageLimits = null;
        if ($subscription && $subscription->plan) {
            $limitService = app(TenantLimitOverageService::class);
            $effectiveService = app(TenantEffectiveLimitService::class);
            $plan = $subscription->plan;

            $usersCurrent = User::count();
            $patientsCurrent = DB::table('patients')->count();
            $appointmentsCurrent = DB::table('appointments')->count();

            $storageUsedBytes = (int) (($tenant->storage_used_bytes ?? 0) + ($tenant->db_used_bytes ?? 0));
            $bandwidthUsedBytes = (int) ($tenant->bandwidth_used_bytes ?? 0);

            $storageUsedMb = round($storageUsedBytes / 1048576, 4);
            $bandwidthUsedMb = round($bandwidthUsedBytes / 1048576, 4);

            $maxStorageMb = (int) $effectiveService->resolveEffectiveLimit(
                $subscription,
                'storage_mb',
                $plan->max_storage_mb ?? config('billing.overage.default_max_storage_mb', 500)
            );
            $maxBandwidthMb = (int) $effectiveService->resolveEffectiveLimit(
                $subscription,
                'bandwidth_mb',
                $plan->max_bandwidth_mb ?? config('billing.overage.default_max_bandwidth_mb', 2048)
            );
            $maxAppointments = (int) $effectiveService->resolveEffectiveLimit($subscription, 'appointments', (int) ($plan->max_appointments ?? 0));

            $buildMetric = function (string $metric, float|int $current, float|int $limit, float $unitPrice, string $unit) use ($limitService, $subscription) {
                $limitNumeric = (float) $limit;
                $currentNumeric = (float) $current;
                $remaining = $limitNumeric > 0 ? max(0.0, $limitNumeric - $currentNumeric) : null;
                $overage = $limitNumeric > 0 ? max(0.0, $currentNumeric - $limitNumeric) : 0.0;

                return [
                    'metric' => $metric,
                    'unit' => $unit,
                    'current' => $currentNumeric,
                    'limit' => $limitNumeric,
                    'remaining' => $remaining,
                    'overage' => $overage,
                    'percent_used' => $limitNumeric > 0 ? min(100, round(($currentNumeric / $limitNumeric) * 100, 2)) : null,
                    'unit_price' => $unitPrice,
                    'projected_overage_amount' => round($overage * $unitPrice, 2),
                    'consent_granted' => in_array($metric, ['users', 'patients', 'appointments'], true)
                        ? $limitService->hasConsent($subscription, $metric)
                        : true,
                ];
            };

            $usageLimits = [
                'users' => $buildMetric('users', $usersCurrent, (int) ($plan->max_users ?? 0), $limitService->getCountMetricPrice('users'), 'count'),
                'patients' => $buildMetric('patients', $patientsCurrent, (int) ($plan->max_patients ?? 0), $limitService->getCountMetricPrice('patients'), 'count'),
                'appointments' => $buildMetric('appointments', $appointmentsCurrent, $maxAppointments, $limitService->getCountMetricPrice('appointments'), 'count'),
                'storage' => $buildMetric('storage', $storageUsedMb, $maxStorageMb, (float) ($plan->storage_overage_price_per_gb ?? 0), 'MB'),
                'bandwidth' => $buildMetric('bandwidth', $bandwidthUsedMb, $maxBandwidthMb, (float) ($plan->bandwidth_overage_price_per_gb ?? 0), 'MB'),
            ];
        }

        // All available plans for upgrade comparison
        $plans = SubscriptionPlan::with('features')
            ->orderBy('price_monthly')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price_monthly' => (float) $plan->price_monthly,
                    'price_yearly' => (float) $plan->price_yearly,
                    'features' => $plan->getFeaturesByCategory(),
                ];
            });

        $paymentHistory = TenantPaymentHistory::query()
            ->latest('paid_at')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(function (TenantPaymentHistory $entry) use ($subscription) {
                return [
                    'id' => $entry->id,
                    'date' => optional($entry->paid_at)->toDateTimeString() ?? optional($entry->created_at)->toDateTimeString(),
                    'transaction_code' => $entry->transaction_code,
                    'payment_method' => $entry->payment_method_label,
                    'plan_name' => $entry->plan_name ?? $subscription?->plan?->name,
                    'amount' => (float) $entry->amount,
                    'currency' => strtoupper((string) ($entry->currency ?? 'PHP')),
                    'status' => $entry->status,
                    'transaction_type' => $entry->transaction_type,
                    'description' => $entry->description,
                    'billed_to_name' => $entry->billed_to_name,
                    'billed_to_email' => $entry->billed_to_email,
                    'billed_to_address' => $entry->billed_to_address,
                    'stripe_payment_intent_id' => $entry->stripe_payment_intent_id,
                    'stripe_charge_id' => $entry->stripe_charge_id,
                    'stripe_invoice_id' => $entry->stripe_invoice_id,
                    'receipt_download_url' => route('settings.payment-history.receipt.download', ['id' => $entry->id]),
                    'invoice_download_url' => $entry->status === 'success'
                        ? route('settings.payment-history.invoice.download', ['id' => $entry->id])
                        : null,
                ];
            })
            ->values();

        return Inertia::render('Tenant/Settings/Index', [
            'days_remaining' => $subscription ? $subscription->days_remaining : null,
            'current_plan_id' => $subscription?->subscription_plan_id,
            'payment_method' => $subscription?->payment_method,
            'stripe_id' => $subscription?->stripe_id,
            'plans' => $plans,
            'payment_history' => $paymentHistory,
            'usage_limits' => $usageLimits,
            'prepaid_context' => app(TenantEffectiveLimitService::class)->getPrepaidContext($subscription),
        ]);
    }

    public function grantOverageConsent(Request $request)
    {
        $validated = $request->validate([
            'metric' => ['required', 'in:users,patients,appointments'],
        ]);

        $tenant = tenant();
        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->latest()
            ->first();

        if (! $subscription) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found for this tenant.',
                ], 422);
            }

            return back()->with('error', 'No active subscription found for this tenant.');
        }

        app(TenantLimitOverageService::class)
            ->grantConsent($subscription, $validated['metric'], optional($request->user())->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Overage consent granted for this billing cycle.',
                'metric' => $validated['metric'],
            ]);
        }

        return back()->with('success', 'Overage consent granted for this billing cycle.');
    }

    /**
     * Display tenant security configuration page.
     */
    public function configuration()
    {
        $tenant = tenant();
        $featureKeys = ['security_settings', 'configuration_settings'];

        $gate = app(TenantFeatureGateService::class)
            ->evaluate((string) $tenant->getTenantKey(), $featureKeys, true);

        if (! $gate['allowed']) {
            if ($gate['reason'] === 'plan_missing_feature' || $gate['reason'] === 'subscription_required') {
                return redirect()->route('settings.features')
                    ->with('error', 'Security settings are not available for your current plan.');
            }

            if ($gate['reason'] === 'update_not_applied') {
                return redirect()->route('settings.updates')
                    ->with('error', 'Security settings are locked. Please apply the security settings update first.');
            }

            return redirect()->route('settings.features')
                ->with('error', 'Security settings feature is not available yet for your plan.');
        }

        return Inertia::render('Tenant/Settings/SecuritySettings', [
            'login_lock' => [
                'max_attempts' => TenantSecuritySettingsService::getLoginMaxAttempts(5),
                'lockout_minutes' => TenantSecuritySettingsService::getLoginLockoutMinutes(15),
            ],
        ]);
    }

    /**
     * Update tenant login lock settings.
     */
    public function updateLoginLockSettings(Request $request)
    {
        $validated = $request->validate([
            'login_max_attempts' => ['required', 'integer', 'min:1', 'max:100'],
            'login_lockout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        TenantSecuritySettingsService::setLoginMaxAttempts((int) $validated['login_max_attempts']);
        TenantSecuritySettingsService::setLoginLockoutMinutes((int) $validated['login_lockout_minutes']);

        return redirect()->back()->with('success', 'Login lock settings updated successfully.');
    }

    /**
     * Display Custom Branding page.
     */
    public function branding()
    {
        $tenant = tenant();
        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->with(['plan.features'])
            ->latest()
            ->first();

        $staff = User::role(['Dentist', 'Assistant'])->get(['id', 'name']);

        // Booking data (for QRCodeSetup)
        $bookingUrl = route('tenant.book.create');
        $qrCode = QrCode::size(200)
            ->format('svg')
            ->generate($bookingUrl);

        // Feature data (for FeatureSettings)
        $otaService = app(FeatureOTAUpdateService::class);
        $pendingUpdates = $otaService->getPendingUpdates($tenant->getTenantKey())
            ->pluck('feature_id')
            ->toArray();

        $featuresByCategory = Feature::ordered()
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
                        'is_enabled' => (bool) $planFeature,
                        'has_pending_update' => in_array($feature->id, $pendingUpdates),
                        'value' => $planFeature ? match ($feature->type) {
                            'boolean' => (bool) data_get($planFeature, 'pivot.value_boolean'),
                            'numeric' => (int) data_get($planFeature, 'pivot.value_numeric'),
                            'tiered' => data_get($planFeature, 'pivot.value_tier'),
                            default => null
                        } : null,
                    ];
                });
            });

        $branding = TenantBrandingService::getAll();

        return Inertia::render('Tenant/CustomBranding/Index', [
            'tenant' => array_merge($tenant->toArray(), [
                'branding_color' => $branding['primary_color'] ?? $tenant->branding_color,
                'font_family' => $branding['font_family'] ?? $tenant->font_family,
                'portal_config' => $branding['portal_config'] ?? $tenant->portal_config,
                'enabled_features' => $tenant->getResolvedEnabledFeaturesForUi($branding),
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
                'sidebar_position' => $branding['sidebar_position'] ?? 'left',
                'support_chat_bottom_offset' => (int) ($branding['support_chat_bottom_offset'] ?? 56),
                'support_chat_right_offset' => (int) ($branding['support_chat_right_offset'] ?? 24),
                'portal_background_type' => $branding['portal_background_type'] ?? 'color',
                'portal_background_color' => $branding['portal_background_color'] ?? null,
                'portal_background_image' => $branding['portal_background_image'] ?? null,
                'portal_background_overlay_opacity' => (int) ($branding['portal_background_overlay_opacity'] ?? 0),
                'ui_sidebar_text_color' => $branding['ui_sidebar_text_color'] ?? null,
                'ui_sidebar_text_size' => (int) ($branding['ui_sidebar_text_size'] ?? 12),
                'ui_sidebar_background_color' => $branding['ui_sidebar_background_color'] ?? null,
                'ui_subnav_background_color' => $branding['ui_subnav_background_color'] ?? null,
                'ui_header_title_color' => $branding['ui_header_title_color'] ?? null,
                'ui_header_title_size' => (int) ($branding['ui_header_title_size'] ?? 20),
                'ui_footer_text_color' => $branding['ui_footer_text_color'] ?? null,
                'ui_footer_text_size' => (int) ($branding['ui_footer_text_size'] ?? 10),
                'ui_footer_background_color' => $branding['ui_footer_background_color'] ?? null,
                'ui_main_text_color' => $branding['ui_main_text_color'] ?? null,
                'ui_main_text_size' => (int) ($branding['ui_main_text_size'] ?? 14),
                'ui_card_background_color' => $branding['ui_card_background_color'] ?? null,
                'ui_card_border_color' => $branding['ui_card_border_color'] ?? null,
                'ui_card_text_color' => $branding['ui_card_text_color'] ?? null,
            ]),
            'subscription' => $subscription,
            'is_premium' => $tenant->canCustomizeBranding(),
            'staff' => $staff,
            'booking_url' => $bookingUrl,
            'qr_code' => (string) $qrCode,
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
        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->with(['plan.features'])
            ->latest()
            ->first();

        if (! $subscription || ! $subscription->plan) {
            return Inertia::render('Tenant/Settings/Features', [
                'features' => [],
            ]);
        }

        // Get pending OTA updates for this tenant
        $otaService = app(FeatureOTAUpdateService::class);
        $pendingUpdates = $otaService->getPendingUpdates($tenant->getTenantKey())
            ->pluck('feature_id')
            ->toArray();

        // Get all active features grouped by category
        $featuresByCategory = Feature::ordered()
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
                        'is_enabled' => (bool) $planFeature,
                        'has_pending_update' => in_array($feature->id, $pendingUpdates),
                        'value' => $planFeature ? match ($feature->type) {
                            'boolean' => (bool) data_get($planFeature, 'pivot.value_boolean'),
                            'numeric' => (int) data_get($planFeature, 'pivot.value_numeric'),
                            'tiered' => data_get($planFeature, 'pivot.value_tier'),
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
        if (! $tenant) {
            abort(404);
        }

        // Normalize target key (handle both space and underscore versions)
        $searchKey = $key;
        $row = DB::table('branding_settings')
            ->where('key', $searchKey)
            ->first();

        if (! $row && str_contains($searchKey, '_')) {
            // Try with space if underscore failed
            $row = DB::table('branding_settings')
                ->where('key', str_replace('_', ' ', $searchKey))
                ->first();
        } elseif (! $row && str_contains($searchKey, ' ')) {
            // Try with underscore if space failed
            $row = DB::table('branding_settings')
                ->where('key', str_replace(' ', '_', $searchKey))
                ->first();
        }

        if (! $row || $row->binary_value === null) {
            // Fallback to tenant model path if available
            $modelMap = [
                'logo_base64' => 'logo_path',
                'logo_login_base64' => 'logo_login_path',
                'logo_booking_base64' => 'logo_booking_path',
            ];

            $modelField = $modelMap[$key] ?? null;
            if ($modelField && $tenant->$modelField) {
                // Return physical file if DB binary is empty
                $path = storage_path('app/public/'.$tenant->$modelField);
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
        $tenant = tenant();
        if (! $tenant) {
            return redirect()->back()->with('error', 'Tenant not found.');
        }

        $rawCurrentOnlineBooking = TenantBrandingService::get('online_booking_enabled', $tenant->isOnlineBookingEnabled());
        $currentOnlineBookingEnabled = filter_var($rawCurrentOnlineBooking, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($currentOnlineBookingEnabled === null) {
            $currentOnlineBookingEnabled = (bool) $rawCurrentOnlineBooking;
        }

        // Decode JSON strings and gracefully force arrays from Inertia FormData
        foreach (['font_family', 'enabled_features', 'landing_page_config', 'portal_config', 'operating_hours'] as $field) {
            $value = $request->input($field);
            if (is_array($value) || is_null($value)) {
                continue;
            }
            if (is_string($value)) {
                $trimmed = trim($value);
                if (in_array($trimmed, ['', 'null', 'undefined', '[]', '{}'])) {
                    $request->merge([$field => []]);
                } elseif (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                    $decoded = json_decode($trimmed, true);
                    $request->merge([$field => (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : []]);
                } else {
                    $request->merge([$field => array_filter(array_map('trim', explode(',', $trimmed)))]);
                }
            } else {
                $request->merge([$field => [$value]]);
            }
        }
        Log::info('BRANDING PAYLOAD ['.$request->method().']', [
            'enabled_features' => $request->input('enabled_features'),
            'type' => gettype($request->input('enabled_features')),
        ]);

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
            'portal_config.apply_to' => 'nullable|in:all,specific',
            'portal_config.selected_staff' => 'nullable|array',
            'portal_config.selected_staff.*' => 'integer|exists:users,id',
            'operating_hours' => 'nullable|array',
            'online_booking_enabled' => 'nullable|boolean',
            'sidebar_position' => 'nullable|in:left,right',
            'support_chat_bottom_offset' => 'nullable|integer|min:16|max:720',
            'support_chat_right_offset' => 'nullable|integer|min:16|max:720',
            'portal_background_type' => 'nullable|in:color,image',
            'portal_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'portal_background_overlay_opacity' => 'nullable|integer|min:0|max:40',
            'ui_sidebar_text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ui_sidebar_text_size' => 'nullable|integer|min:10|max:18',
            'ui_sidebar_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ui_subnav_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ui_header_title_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ui_header_title_size' => 'nullable|integer|min:16|max:36',
            'ui_footer_text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ui_footer_text_size' => 'nullable|integer|min:8|max:18',
            'ui_footer_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ui_main_text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ui_main_text_size' => 'nullable|integer|min:12|max:20',
            'ui_card_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ui_card_border_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ui_card_text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        if (isset($validated['portal_config']) && is_array($validated['portal_config'])) {
            $validated['portal_config'] = $this->normalizePortalConfig($validated['portal_config']);
        }

        // Store branding settings in tenant database (Primary Source for Visuals)
        if (isset($validated['clinic_name'])) {
            TenantBrandingService::set('clinic_name', $validated['clinic_name']);
        }
        if (isset($validated['email'])) {
            TenantBrandingService::set('clinic_email', $validated['email']);
        }
        if (isset($validated['phone'])) {
            TenantBrandingService::set('clinic_phone', $validated['phone']);
        }
        if (isset($validated['address'])) {
            TenantBrandingService::set('clinic_address', $validated['address']);
        }

        if (isset($validated['branding_color'])) {
            TenantBrandingService::set('primary_color', $validated['branding_color']);
        }
        if (isset($validated['font_family'])) {
            TenantBrandingService::set('font_family', $validated['font_family']);
        }
        if (isset($validated['enabled_features'])) {
            TenantBrandingService::set('enabled_features', $validated['enabled_features']);
        }
        if (isset($validated['landing_page_config'])) {
            TenantBrandingService::set('landing_page_config', $validated['landing_page_config']);
        }
        if (isset($validated['portal_config'])) {
            TenantBrandingService::set('portal_config', $validated['portal_config']);
        }
        if (isset($validated['operating_hours'])) {
            TenantBrandingService::set('operating_hours', $validated['operating_hours']);
        }
        if (array_key_exists('online_booking_enabled', $validated)) {
            TenantBrandingService::set('online_booking_enabled', (bool) $validated['online_booking_enabled']);
        }
        if (isset($validated['sidebar_position'])) {
            TenantBrandingService::set('sidebar_position', $validated['sidebar_position']);
        }
        if (isset($validated['support_chat_bottom_offset'])) {
            TenantBrandingService::set('support_chat_bottom_offset', $validated['support_chat_bottom_offset']);
        }
        if (isset($validated['support_chat_right_offset'])) {
            TenantBrandingService::set('support_chat_right_offset', $validated['support_chat_right_offset']);
        }
        if (isset($validated['portal_background_type'])) {
            TenantBrandingService::set('portal_background_type', $validated['portal_background_type']);
        }
        if (isset($validated['portal_background_color'])) {
            TenantBrandingService::set('portal_background_color', $validated['portal_background_color']);
        }
        if (isset($validated['portal_background_overlay_opacity'])) {
            TenantBrandingService::set('portal_background_overlay_opacity', (int) $validated['portal_background_overlay_opacity']);
        }
        if (isset($validated['ui_sidebar_text_color'])) {
            TenantBrandingService::set('ui_sidebar_text_color', $validated['ui_sidebar_text_color']);
        }
        if (isset($validated['ui_sidebar_text_size'])) {
            TenantBrandingService::set('ui_sidebar_text_size', (int) $validated['ui_sidebar_text_size']);
        }
        if (isset($validated['ui_sidebar_background_color'])) {
            TenantBrandingService::set('ui_sidebar_background_color', $validated['ui_sidebar_background_color']);
        }
        if (isset($validated['ui_subnav_background_color'])) {
            TenantBrandingService::set('ui_subnav_background_color', $validated['ui_subnav_background_color']);
        }
        if (isset($validated['ui_header_title_color'])) {
            TenantBrandingService::set('ui_header_title_color', $validated['ui_header_title_color']);
        }
        if (isset($validated['ui_header_title_size'])) {
            TenantBrandingService::set('ui_header_title_size', (int) $validated['ui_header_title_size']);
        }
        if (isset($validated['ui_footer_text_color'])) {
            TenantBrandingService::set('ui_footer_text_color', $validated['ui_footer_text_color']);
        }
        if (isset($validated['ui_footer_text_size'])) {
            TenantBrandingService::set('ui_footer_text_size', (int) $validated['ui_footer_text_size']);
        }
        if (isset($validated['ui_footer_background_color'])) {
            TenantBrandingService::set('ui_footer_background_color', $validated['ui_footer_background_color']);
        }
        if (isset($validated['ui_main_text_color'])) {
            TenantBrandingService::set('ui_main_text_color', $validated['ui_main_text_color']);
        }
        if (isset($validated['ui_main_text_size'])) {
            TenantBrandingService::set('ui_main_text_size', (int) $validated['ui_main_text_size']);
        }
        if (isset($validated['ui_card_background_color'])) {
            TenantBrandingService::set('ui_card_background_color', $validated['ui_card_background_color']);
        }
        if (isset($validated['ui_card_border_color'])) {
            TenantBrandingService::set('ui_card_border_color', $validated['ui_card_border_color']);
        }
        if (isset($validated['ui_card_text_color'])) {
            TenantBrandingService::set('ui_card_text_color', $validated['ui_card_text_color']);
        }

        if (isset($validated['hero_title'])) {
            TenantBrandingService::set('hero_title', $validated['hero_title']);
        }
        if (isset($validated['hero_subtitle'])) {
            TenantBrandingService::set('hero_subtitle', $validated['hero_subtitle']);
        }
        if (isset($validated['about_us_description'])) {
            TenantBrandingService::set('about_us_description', $validated['about_us_description']);
        }

        // Sync to Tenants Table in Central DB (Secondary Source for Discovery/Admin)
        if (isset($validated['clinic_name'])) {
            $tenant->name = $validated['clinic_name'];
        }
        if (isset($validated['address'])) {
            $tenant->street = $validated['address'];
        }
        if (isset($validated['email'])) {
            $tenant->email = $validated['email'];
        }
        if (isset($validated['phone'])) {
            $tenant->phone = $validated['phone'];
        }

        // Apply Plan-Based Gating for premium visuals
        if (! $tenant->canCustomizeBranding()) {
            // Basic plan: Ignore premium fields for the model update
            unset(
                $validated['branding_color'],
                $validated['font_family'],
                $validated['operating_hours']
            );
        } else {
            // Premium: Sync visual state to model for central accessibility
            $data = $tenant->data ?? [];
            if (isset($validated['branding_color'])) {
                $data['branding_color'] = $validated['branding_color'];
            }
            if (isset($validated['font_family'])) {
                $data['font_family'] = $validated['font_family'];
            }
            if (isset($validated['portal_config'])) {
                $data['portal_config'] = $validated['portal_config'];
            }
            if (isset($validated['operating_hours'])) {
                $data['operating_hours'] = $validated['operating_hours'];
            }
            if (array_key_exists('online_booking_enabled', $validated)) {
                $data['online_booking_enabled'] = $validated['online_booking_enabled'];
            }
            $tenant->data = $data;
        }

        $tenant->save();

        // Clean up internal keys before update
        unset(
            $validated['clinic_name'],
            $validated['address']
        );

        // Also update regular columns
        $tenant->update($validated);

        if (array_key_exists('online_booking_enabled', $validated)) {
            $newOnlineBookingEnabled = (bool) $validated['online_booking_enabled'];

            if ($newOnlineBookingEnabled !== $currentOnlineBookingEnabled) {
                try {
                    broadcast(new OnlineBookingStatusUpdated((string) $tenant->getTenantKey(), $newOnlineBookingEnabled));
                } catch (\Throwable $e) {
                    Log::warning('Online booking broadcast failed', [
                        'tenant_id' => (string) $tenant->getTenantKey(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $shouldBroadcastBranding = isset($validated['clinic_name'])
            || isset($validated['email'])
            || isset($validated['phone'])
            || isset($validated['address'])
            || isset($validated['branding_color'])
            || isset($validated['font_family'])
            || isset($validated['sidebar_position'])
            || isset($validated['support_chat_bottom_offset'])
            || isset($validated['support_chat_right_offset'])
            || isset($validated['portal_background_type'])
            || isset($validated['portal_background_color'])
            || isset($validated['portal_background_overlay_opacity'])
            || isset($validated['ui_sidebar_text_color'])
            || isset($validated['ui_sidebar_text_size'])
            || isset($validated['ui_sidebar_background_color'])
            || isset($validated['ui_subnav_background_color'])
            || isset($validated['ui_header_title_color'])
            || isset($validated['ui_header_title_size'])
            || isset($validated['ui_footer_text_color'])
            || isset($validated['ui_footer_text_size'])
            || isset($validated['ui_footer_background_color'])
            || isset($validated['ui_main_text_color'])
            || isset($validated['ui_main_text_size'])
            || isset($validated['ui_card_background_color'])
            || isset($validated['ui_card_border_color'])
            || isset($validated['ui_card_text_color'])
            || isset($validated['enabled_features'])
            || isset($validated['landing_page_config'])
            || isset($validated['portal_config'])
            || isset($validated['hero_title'])
            || isset($validated['hero_subtitle'])
            || isset($validated['about_us_description'])
            || isset($validated['operating_hours'])
            || array_key_exists('online_booking_enabled', $validated);

        if ($shouldBroadcastBranding) {
            try {
                $this->broadcastTenantBrandingUpdate($tenant);
            } catch (\Throwable $e) {
                Log::warning('Tenant branding broadcast failed', [
                    'tenant_id' => (string) $tenant->getTenantKey(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Clinic settings updated successfully.');
    }

    private function normalizePortalConfig(array $portalConfig): array
    {
        $applyTo = ($portalConfig['apply_to'] ?? 'all') === 'specific' ? 'specific' : 'all';

        $selectedStaff = $portalConfig['selected_staff'] ?? [];
        if (! is_array($selectedStaff)) {
            $selectedStaff = [];
        }

        $selectedStaff = array_values(array_unique(array_map(
            fn ($id) => (int) $id,
            array_filter($selectedStaff, fn ($id) => is_numeric($id))
        )));

        if ($applyTo === 'all') {
            $selectedStaff = [];
        }

        return [
            'apply_to' => $applyTo,
            'selected_staff' => $selectedStaff,
        ];
    }

    private function buildLandingConfigBroadcastPayload($rawConfig): array
    {
        $config = $rawConfig;

        if (is_string($config) && $config !== '') {
            $decoded = json_decode($config, true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }

        if (! is_array($config)) {
            $config = [];
        }

        $sections = is_array($config['sections'] ?? null) ? $config['sections'] : [];
        $team = is_array($config['team'] ?? null) ? $config['team'] : [];
        $manualCards = array_slice(is_array($team['manual_cards'] ?? null) ? $team['manual_cards'] : [], 0, 12);

        // Normalize individual section arrays to avoid undefined index notices
        $heroSection = is_array($sections['hero'] ?? null) ? $sections['hero'] : [];
        $contentSection = is_array($sections['content'] ?? null) ? $sections['content'] : [];
        $servicesSection = is_array($sections['services'] ?? null) ? $sections['services'] : [];
        $teamSection = is_array($sections['team'] ?? null) ? $sections['team'] : [];
        $contactSection = is_array($sections['contact'] ?? null) ? $sections['contact'] : [];

        $sanitizedCards = array_values(array_filter(array_map(function ($card, $index) {
            if (! is_array($card)) {
                return null;
            }

            $imageUrl = is_string($card['image_url'] ?? null) ? $card['image_url'] : '';

            // Avoid oversized realtime payloads by stripping inline base64 blobs.
            if (str_starts_with($imageUrl, 'data:image/')) {
                $imageUrl = '';
            }

            if (strlen($imageUrl) > 2048) {
                $imageUrl = '';
            }

            return [
                'id' => is_string($card['id'] ?? null) ? $card['id'] : ('manual-'.$index),
                'name' => is_string($card['name'] ?? null) ? $card['name'] : '',
                'role' => is_string($card['role'] ?? null) ? $card['role'] : '',
                'bio' => is_string($card['bio'] ?? null) ? $card['bio'] : '',
                'image_url' => $imageUrl,
            ];
        }, $manualCards, array_keys($manualCards)), fn ($card) => is_array($card)));

        return [
            'background_color' => is_string($config['background_color'] ?? null) ? $config['background_color'] : '#ffffff',
            'text_primary' => is_string($config['text_primary'] ?? null) ? $config['text_primary'] : '#111827',
            'text_secondary' => is_string($config['text_secondary'] ?? null) ? $config['text_secondary'] : '#4b5563',
            'operating_hours_style' => [
                'section_background' => is_string($config['operating_hours_style']['section_background'] ?? null) ? $config['operating_hours_style']['section_background'] : '#111827',
                'section_title_color' => is_string($config['operating_hours_style']['section_title_color'] ?? null) ? $config['operating_hours_style']['section_title_color'] : '#ffffff',
                'section_border_color' => is_string($config['operating_hours_style']['section_border_color'] ?? null) ? $config['operating_hours_style']['section_border_color'] : '#1f2937',
                'card_open_background' => is_string($config['operating_hours_style']['card_open_background'] ?? null) ? $config['operating_hours_style']['card_open_background'] : '#1f2937',
                'card_closed_background' => is_string($config['operating_hours_style']['card_closed_background'] ?? null) ? $config['operating_hours_style']['card_closed_background'] : '#111827',
                'card_open_day_color' => is_string($config['operating_hours_style']['card_open_day_color'] ?? null) ? $config['operating_hours_style']['card_open_day_color'] : '#ffffff',
                'card_closed_day_color' => is_string($config['operating_hours_style']['card_closed_day_color'] ?? null) ? $config['operating_hours_style']['card_closed_day_color'] : '#fca5a5',
                'card_time_color' => is_string($config['operating_hours_style']['card_time_color'] ?? null) ? $config['operating_hours_style']['card_time_color'] : '#9ca3af',
                'closed_label_color' => is_string($config['operating_hours_style']['closed_label_color'] ?? null) ? $config['operating_hours_style']['closed_label_color'] : '#fda4af',
                'copyright_color' => is_string($config['operating_hours_style']['copyright_color'] ?? null) ? $config['operating_hours_style']['copyright_color'] : '#6b7280',
            ],
            'sections' => [
                'hero' => [
                    'active' => (bool) ($heroSection['active'] ?? true),
                    'background_type' => in_array(($heroSection['background_type'] ?? 'color'), ['color', 'image'], true) ? $heroSection['background_type'] : 'color',
                    'background_color' => $heroSection['background_color'] ?? '#f9fafb',
                    'background_image' => $heroSection['background_image'] ?? null,
                    'badge_text' => $heroSection['badge_text'] ?? 'Expert Dental Care',
                    'cta_text' => $heroSection['cta_text'] ?? 'Schedule Your Visit',
                ],
                'content' => [
                    'active' => (bool) ($contentSection['active'] ?? true),
                    'image' => $contentSection['image'] ?? null,
                    'title' => $contentSection['title'] ?? 'Committed to Excellence in Dental Care',
                    'subtitle' => $contentSection['subtitle'] ?? 'Our clinic is dedicated to providing the best dental care in the region. Our team of experienced professionals is here to ensure your smile remains healthy and beautiful.',
                    'highlights' => is_array($contentSection['highlights'] ?? null) ? array_values(array_slice($contentSection['highlights'], 0, 5)) : ['Modern Technology', 'Sterilized Environment', 'Compassionate Experts'],
                    'background_type' => in_array(($contentSection['background_type'] ?? 'color'), ['color', 'image'], true) ? $contentSection['background_type'] : 'color',
                    'background_color' => $contentSection['background_color'] ?? '#f9fafb',
                    'background_image' => $contentSection['background_image'] ?? null,
                ],
                'services' => [
                    'active' => (bool) ($servicesSection['active'] ?? true),
                    'image' => $servicesSection['image'] ?? null,
                    'title' => $servicesSection['title'] ?? 'Our Specialized Services',
                    'subtitle' => $servicesSection['subtitle'] ?? 'We offer a wide range of dental treatments to keep your clinic healthy and your smile glowing.',
                    'background_type' => in_array(($servicesSection['background_type'] ?? 'color'), ['color', 'image'], true) ? $servicesSection['background_type'] : 'color',
                    'background_color' => $servicesSection['background_color'] ?? '#ffffff',
                    'background_image' => $servicesSection['background_image'] ?? null,
                ],
                'team' => [
                    'active' => (bool) ($teamSection['active'] ?? true),
                    'image' => $teamSection['image'] ?? null,
                    'title' => $teamSection['title'] ?? 'Meet Our Specialist Team',
                    'subtitle' => $teamSection['subtitle'] ?? 'Expert dentists dedicated to provide world-class dental treatments with care.',
                    'background_type' => in_array(($teamSection['background_type'] ?? 'color'), ['color', 'image'], true) ? $teamSection['background_type'] : 'color',
                    'background_color' => $teamSection['background_color'] ?? '#ffffff',
                    'background_image' => $teamSection['background_image'] ?? null,
                ],
                'contact' => [
                    'active' => (bool) ($contactSection['active'] ?? true),
                    'image' => $contactSection['image'] ?? null,
                    'title' => $contactSection['title'] ?? "Have a Concern? We're Here to Help.",
                    'subtitle' => $contactSection['subtitle'] ?? "Whether you're looking for an appointment or have a general inquiry, feel free to send us a message. Our team will respond as quickly as possible.",
                    'background_type' => in_array(($contactSection['background_type'] ?? 'color'), ['color', 'image'], true) ? $contactSection['background_type'] : 'color',
                    'background_color' => $contactSection['background_color'] ?? '#ffffff',
                    'background_image' => $contactSection['background_image'] ?? null,
                ],
            ],
            'team' => [
                'source_mode' => in_array(($team['source_mode'] ?? 'auto_staff'), ['auto_staff', 'manual', 'hybrid'], true)
                    ? $team['source_mode']
                    : 'auto_staff',
                'include_owner' => (bool) ($team['include_owner'] ?? true),
                'manual_cards' => $sanitizedCards,
            ],
        ];
    }

    private function buildBrandingBroadcastPayload($tenant): array
    {
        $latestBranding = TenantBrandingService::getAll();
        $resolvedEnabledFeatures = $tenant->getResolvedEnabledFeaturesForUi($latestBranding);
        $landingConfigPayload = $this->buildLandingConfigBroadcastPayload(
            $latestBranding['landing_page_config'] ?? ($tenant->landing_page_config ?? [])
        );

        $resolvedPortalConfig = $this->normalizePortalConfig(
            is_array($latestBranding['portal_config'] ?? null)
                ? $latestBranding['portal_config']
                : (is_array($tenant->portal_config ?? null) ? $tenant->portal_config : [])
        );

        return [
            'clinic_name' => $latestBranding['clinic_name'] ?? $tenant->name,
            'email' => $latestBranding['clinic_email'] ?? $tenant->email,
            'phone' => $latestBranding['clinic_phone'] ?? $tenant->phone,
            'address' => $latestBranding['clinic_address'] ?? $tenant->street,
            'primary_color' => $latestBranding['primary_color'] ?? ($tenant->branding_color ?: '#0ea5e9'),
            'branding_color' => $latestBranding['primary_color'] ?? ($tenant->branding_color ?: '#0ea5e9'),
            'font_family' => $latestBranding['font_family'] ?? $tenant->font_family,
            'sidebar_position' => $latestBranding['sidebar_position'] ?? $tenant->sidebar_position,
            'support_chat_bottom_offset' => (int) ($latestBranding['support_chat_bottom_offset'] ?? $tenant->support_chat_bottom_offset ?? 56),
            'support_chat_right_offset' => (int) ($latestBranding['support_chat_right_offset'] ?? $tenant->support_chat_right_offset ?? 24),
            'portal_background_type' => $latestBranding['portal_background_type'] ?? ($tenant->portal_background_type ?: 'color'),
            'portal_background_color' => $latestBranding['portal_background_color'] ?? ($tenant->portal_background_color ?: null),
            'portal_background_image' => $latestBranding['portal_background_image'] ?? ($tenant->portal_background_image ?: null),
            'portal_background_overlay_opacity' => (int) ($latestBranding['portal_background_overlay_opacity'] ?? $tenant->portal_background_overlay_opacity ?? 0),
            'ui_sidebar_text_color' => $latestBranding['ui_sidebar_text_color'] ?? ($tenant->ui_sidebar_text_color ?: null),
            'ui_sidebar_text_size' => (int) ($latestBranding['ui_sidebar_text_size'] ?? $tenant->ui_sidebar_text_size ?? 12),
            'ui_sidebar_background_color' => $latestBranding['ui_sidebar_background_color'] ?? ($tenant->ui_sidebar_background_color ?: null),
            'ui_subnav_background_color' => $latestBranding['ui_subnav_background_color'] ?? ($tenant->ui_subnav_background_color ?: null),
            'ui_header_title_color' => $latestBranding['ui_header_title_color'] ?? ($tenant->ui_header_title_color ?: null),
            'ui_header_title_size' => (int) ($latestBranding['ui_header_title_size'] ?? $tenant->ui_header_title_size ?? 20),
            'ui_footer_text_color' => $latestBranding['ui_footer_text_color'] ?? ($tenant->ui_footer_text_color ?: null),
            'ui_footer_text_size' => (int) ($latestBranding['ui_footer_text_size'] ?? $tenant->ui_footer_text_size ?? 10),
            'ui_footer_background_color' => $latestBranding['ui_footer_background_color'] ?? ($tenant->ui_footer_background_color ?: null),
            'ui_main_text_color' => $latestBranding['ui_main_text_color'] ?? ($tenant->ui_main_text_color ?: null),
            'ui_main_text_size' => (int) ($latestBranding['ui_main_text_size'] ?? $tenant->ui_main_text_size ?? 14),
            'ui_card_background_color' => $latestBranding['ui_card_background_color'] ?? ($tenant->ui_card_background_color ?: null),
            'ui_card_border_color' => $latestBranding['ui_card_border_color'] ?? ($tenant->ui_card_border_color ?: null),
            'ui_card_text_color' => $latestBranding['ui_card_text_color'] ?? ($tenant->ui_card_text_color ?: null),
            'enabled_features' => $resolvedEnabledFeatures,
            'landing_page_config' => $landingConfigPayload,
            'portal_config' => $resolvedPortalConfig,
            'hero_title' => $latestBranding['hero_title'] ?? $tenant->hero_title,
            'hero_subtitle' => $latestBranding['hero_subtitle'] ?? $tenant->hero_subtitle,
            'about_us_description' => $latestBranding['about_us_description'] ?? $tenant->about_us_description,
            'operating_hours' => $latestBranding['operating_hours'] ?? $tenant->getOperatingHoursWithDefaults(),
            'online_booking_enabled' => (bool) ($latestBranding['online_booking_enabled'] ?? $tenant->isOnlineBookingEnabled()),
            'logo_path' => $latestBranding['logo_base64'] ?? null,
            'logo_login_path' => $latestBranding['logo_login_base64'] ?? null,
            'logo_booking_path' => $latestBranding['logo_booking_base64'] ?? null,
            'portal_logo' => $latestBranding['logo_base64'] ?? null,
        ];
    }

    private function broadcastTenantBrandingUpdate($tenant): void
    {
        broadcast(new TenantBrandingUpdated(
            (string) $tenant->getTenantKey(),
            $this->buildBrandingBroadcastPayload($tenant)
        ));
    }

    /**
     * Persist draggable support chat bubble position.
     */
    public function updateSupportChatPosition(Request $request)
    {
        $validated = $request->validate([
            'support_chat_bottom_offset' => 'required|integer|min:16|max:720',
            'support_chat_right_offset' => 'required|integer|min:16|max:720',
        ]);

        TenantBrandingService::set('support_chat_bottom_offset', (int) $validated['support_chat_bottom_offset']);
        TenantBrandingService::set('support_chat_right_offset', (int) $validated['support_chat_right_offset']);

        return response()->json([
            'success' => true,
            'support_chat_bottom_offset' => (int) $validated['support_chat_bottom_offset'],
            'support_chat_right_offset' => (int) $validated['support_chat_right_offset'],
        ]);
    }

    /**
     * Upload a single logo via AJAX (memory-safe, decoupled from main form).
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'field' => 'required|in:logo,logo_login,logo_booking,landing_content,landing_services,landing_team,landing_contact,landing_bg_hero,landing_bg_content,landing_bg_services,landing_bg_team,landing_bg_contact,portal_background,landing_team_card',
            'card_id' => 'nullable|string|max:80',
            'image' => 'required|image|mimes:jpeg,png,gif,webp,svg|max:2048',
        ]);

        $tenant = tenant();
        if (! $tenant) {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }

        $field = $request->input('field');
        $file = $request->file('image');
        $cardId = (string) $request->input('card_id', '');

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
            'landing_content' => 'landing_image_content',
            'landing_services' => 'landing_image_services',
            'landing_team' => 'landing_image_team',
            'landing_contact' => 'landing_image_contact',
            'landing_bg_hero' => 'landing_bg_image_hero',
            'landing_bg_content' => 'landing_bg_image_content',
            'landing_bg_services' => 'landing_bg_image_services',
            'landing_bg_team' => 'landing_bg_image_team',
            'landing_bg_contact' => 'landing_bg_image_contact',
            'portal_background' => 'portal_background_image',
        ];

        if ($field === 'landing_team_card') {
            $safeCardId = preg_replace('/[^A-Za-z0-9_-]/', '', $cardId);
            if ($safeCardId === '') {
                return response()->json(['error' => 'Invalid team card identifier.'], 422);
            }
            $storageKey = 'landing_team_card_'.$safeCardId;
        } else {
            $storageKey = $keyMap[$field];
        }

        $oldBlobBytes = $this->getBrandingBinarySize($storageKey);

        TenantBrandingService::setStreamed($storageKey, $file->path());
        $newBlobBytes = $this->getBrandingBinarySize($storageKey);
        $this->adjustTenantStorageBytes($tenant, $newBlobBytes - $oldBlobBytes);

        // Sync to central tenants table so system knows a binary logo exists
        $modelMap = [
            'logo_base64' => 'logo_path',
            'logo_login_base64' => 'logo_login_path',
            'logo_booking_base64' => 'logo_booking_path',
        ];

        $modelField = $modelMap[$storageKey] ?? null;
        if ($modelField) {
            try {
                DB::connection('central')->table('tenants')
                    ->where('id', $tenant->id)
                    ->update([$modelField => $storageKey]); // Store the key name as the "path"
            } catch (\Exception $e) {
                Log::error('Clinic branding central sync error: '.$e->getMessage());
            }
        }

        try {
            $this->broadcastTenantBrandingUpdate($tenant->fresh());
        } catch (\Throwable $e) {
            Log::warning('Tenant branding broadcast failed after logo upload', [
                'tenant_id' => (string) $tenant->getTenantKey(),
                'field' => $field,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'url' => route('settings.logo', ['key' => $storageKey]).'?v='.time(),
        ]);
    }

    /**
     * Delete a single logo via AJAX.
     */
    public function deleteLogo(Request $request)
    {
        $request->validate([
            'field' => 'required|in:logo,logo_login,logo_booking,landing_content,landing_services,landing_team,landing_contact,landing_bg_hero,landing_bg_content,landing_bg_services,landing_bg_team,landing_bg_contact,portal_background',
        ]);

        $tenant = tenant();
        if (! $tenant) {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }

        $field = $request->input('field');
        $keyMap = [
            'logo' => 'logo_base64',
            'logo_login' => 'logo_login_base64',
            'logo_booking' => 'logo_booking_base64',
            'landing_content' => 'landing_image_content',
            'landing_services' => 'landing_image_services',
            'landing_team' => 'landing_image_team',
            'landing_contact' => 'landing_image_contact',
            'landing_bg_hero' => 'landing_bg_image_hero',
            'landing_bg_content' => 'landing_bg_image_content',
            'landing_bg_services' => 'landing_bg_image_services',
            'landing_bg_team' => 'landing_bg_image_team',
            'landing_bg_contact' => 'landing_bg_image_contact',
            'portal_background' => 'portal_background_image',
        ];

        try {
            $oldBlobBytes = $this->getBrandingBinarySize($keyMap[$field]);
            DB::table('branding_settings')->where('key', $keyMap[$field])->delete();
            TenantBrandingService::forget($keyMap[$field]);
            $this->adjustTenantStorageBytes($tenant, -$oldBlobBytes);

            // Also clear central tenants table column
            $modelMap = [
                'logo_base64' => 'logo_path',
                'logo_login_base64' => 'logo_login_path',
                'logo_booking_base64' => 'logo_booking_path',
            ];
            $modelField = $modelMap[$keyMap[$field]] ?? null;
            if ($modelField) {
                DB::connection('central')->table('tenants')
                    ->where('id', $tenant->id)
                    ->update([$modelField => null]);
            }

            try {
                $this->broadcastTenantBrandingUpdate($tenant->fresh());
            } catch (\Throwable $e) {
                Log::warning('Tenant branding broadcast failed after logo delete', [
                    'tenant_id' => (string) $tenant->getTenantKey(),
                    'field' => $field,
                    'error' => $e->getMessage(),
                ]);
            }
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
        $releaseService = app(ReleaseService::class);

        // 0. Trigger the global system check command to sync with GitHub
        // This ensures the local database is up-to-date with the latest releases.
        try {
            Artisan::call('system:check-updates');
        } catch (\Exception $e) {
            Log::error('Failed to run system:check-updates from UI: ' . $e->getMessage());
        }

        // 1. Check Central API if configured (Laptop B polling Laptop A)
        // Prioritize .env/config if set, then database
        $centralUrl = config('app.central_api_url') ?: env('CENTRAL_API_URL') ?: SystemSetting::get('central_api_url');
        
        if ($centralUrl || empty($centralUrl)) {
            try {
                $fullUrl = $centralUrl;
                if (empty($fullUrl) || !str_starts_with($fullUrl, 'http')) {
                    // If relative, use the current request host
                    $fullUrl = request()->getSchemeAndHttpHost() . '/' . ltrim($fullUrl, '/');
                }

                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'ngrok-skip-browser-warning' => 'true',
                ])->timeout(15)->get(rtrim($fullUrl, '/') . '/api/central/latest-version');
                if ($response->successful()) {
                    $data = $response->json();
                    $version = $data['version'];

                    // Sync to local SystemRelease
                    $systemRelease = SystemRelease::firstOrCreate(
                        ['version' => $version],
                        [
                            'release_notes' => $data['release_notes'] ?? null,
                            'released_at' => now(),
                            'is_mandatory' => false,
                            'requires_db_update' => (bool) ($data['requires_db_update'] ?? false),
                        ]
                    );

                    // Sync to local Feature
                    $cleanVersion = ltrim($version, 'vV');
                    $featureKey = 'system_version_' . str_replace('.', '_', $cleanVersion);
                    $feature = Feature::firstOrCreate(
                        ['key' => $featureKey],
                        [
                            'name' => "System Update: {$version}",
                            'description' => 'Official platform update released via Central.',
                            'type' => 'system_version',
                            'category' => 'expansion',
                            'is_active' => true,
                            'implementation_status' => Feature::STATUS_ACTIVE,
                            'system_release_id' => $systemRelease->id,
                            'released_at' => $systemRelease->released_at,
                        ]
                    );

                    // Notify tenant
                    TenantFeatureUpdate::firstOrCreate([
                        'tenant_id' => $tenant->getTenantKey(),
                        'feature_id' => $feature->id,
                    ], [
                        'status' => TenantFeatureUpdate::STATUS_PENDING,
                    ]);
                    
                    \Illuminate\Support\Facades\Cache::forget("tenant_{$tenant->getTenantKey()}_pending_updates_count");
                }
            } catch (\Exception $e) {
                Log::warning("Failed to poll central API at {$centralUrl}: " . $e->getMessage());
            }
        }

        $pendingUpdates = $otaService->getPendingUpdates($tenant->getTenantKey());

        $updates = $pendingUpdates->map(function ($u) {
            $feature = $u->feature;

            return [
                'id' => $feature->id ?? null,
                'name' => $feature->name ?? 'Unknown Feature',
                'description' => $feature->description ?? '',
                'type' => $feature->type ?? null,
                'system_release' => $feature->system_release ? [
                    'version' => $feature->system_release->version ?? null,
                    'release_notes' => $feature->system_release->release_notes ?? null,
                    'requires_db_update' => (bool) ($feature->system_release->requires_db_update ?? false),
                ] : null,
            ];
        })->values();

        return response()->json([
            'has_updates' => $updates->count() > 0,
            'count' => $updates->count(),
            'updates' => $updates,
        ]);
    }

    private function getBrandingBinarySize(string $key): int
    {
        $bytes = DB::table('branding_settings')
            ->where('key', $key)
            ->selectRaw('COALESCE(OCTET_LENGTH(binary_value), 0) as bytes')
            ->value('bytes');

        return (int) ($bytes ?? 0);
    }

    private function adjustTenantStorageBytes($tenant, int $deltaBytes): void
    {
        if ($deltaBytes === 0) {
            return;
        }

        try {
            $current = (int) DB::connection('central')->table('tenants')
                ->where('id', $tenant->id)
                ->value('storage_used_bytes');

            $next = max(0, $current + $deltaBytes);

            DB::connection('central')->table('tenants')
                ->where('id', $tenant->id)
                ->update(['storage_used_bytes' => $next]);
        } catch (\Throwable $e) {
            Log::warning('Failed to update tenant storage usage for branding media', [
                'tenant_id' => $tenant->id,
                'delta_bytes' => $deltaBytes,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
