<?php

namespace App\Http\Middleware;

use Detection\MobileDetect;
use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\SystemSetting;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $detect = new MobileDetect();

        return [
            ...parent::share($request),
            'device' => [
                'isMobile' => $detect->isMobile(),
                'isTablet' => $detect->isTablet(),
                'isDesktop' => !$detect->isMobile() && !$detect->isTablet(),
            ],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'profile_picture_url' => $request->user()->profile_picture_url,
                    'roles' => $request->user()->getRoleNames()->toArray(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
                    'requires_password_change' => (bool)$request->user()->requires_password_change,
                ] : null,
            ],
            'config' => [
                'central_domain' => config('tenancy.central_domains.0', 'localhost'),
                'app_url' => $request->getSchemeAndHttpHost(),
                'recaptcha_site_key' => config('services.recaptcha.site_key', ''),
                'version' => fn() => tenant() ? (tenant()->version ?: 'v1.0.0') : \App\Services\AppVersionService::getVersion(),
            ],
            'tenant' => function () {
            $tenant = tenant();
            if (!$tenant)
                return null;

            // Optimized: Only fetch what's needed for the layout/gating
            // Priority: TenantBrandingService (Binary/Custom) > Tenant Model (Legacy/Sync)
            $branding = \App\Services\TenantBrandingService::getAll();

            // GRACEFUL DEGRADATION: 
            // If the tenant currently has access to Custom Branding (Pro), respect their hidden/shown overrides.
            // If they are downgraded to Basic, ignore their overrides and fallback to default immediately to prevent menu lockouts.
            $enabledFeatures = $tenant->canCustomizeBranding()
                ? ($branding['enabled_features'] ?? $tenant->enabled_features ?? \App\Models\Tenant::getDefaultFeatures())
                : \App\Models\Tenant::getDefaultFeatures();

            return array_merge($tenant->toArray(), [
                    'branding_color' => $branding['primary_color'] ?? $tenant->branding_color,
                    'font_family' => $branding['font_family'] ?? $tenant->font_family,
                    'portal_config' => $branding['portal_config'] ?? $tenant->portal_config,
                    'landing_page_config' => $branding['landing_page_config'] ?? $tenant->landing_page_config,
                    'hero_title' => $branding['hero_title'] ?? $tenant->hero_title,
                    'hero_subtitle' => $branding['hero_subtitle'] ?? $tenant->hero_subtitle,
                    'about_us_description' => $branding['about_us_description'] ?? $tenant->about_us_description,
                    'operating_hours' => $branding['operating_hours'] ?? $tenant->operating_hours,
                    'online_booking_enabled' => $branding['online_booking_enabled'] ?? $tenant->online_booking_enabled ?? true,
                    'enabled_features' => $enabledFeatures,
                    'logo_path' => $branding['logo_base64'] ?? $tenant->logo_path,
                    'logo_login_path' => $branding['logo_login_base64'] ?? $tenant->logo_login_path,
                    'logo_booking_path' => $branding['logo_booking_base64'] ?? $tenant->logo_booking_path,
                    'is_premium' => $tenant->canCustomizeBranding(),
                ]);
        },
            'tenant_plan' => function () {
            $tenant = tenant();
            if (!$tenant) {
                return null;
            }

            $previewTenantId = (string)config('tenancy.preview.tenant_id', 'preview-sandbox');
            $isPreviewTenant = (string)$tenant->getTenantKey() === $previewTenantId;

            return [
                    'features' => [
                        'sms_notifications' => $isPreviewTenant ? true : $tenant->hasPlanFeature('sms_notifications'),
                        'custom_branding' => $isPreviewTenant ? true : $tenant->hasPlanFeature('custom_branding'),
                        'advanced_analytics' => $isPreviewTenant ? true : $tenant->hasPlanFeature('advanced_analytics'),
                        'priority_support' => $isPreviewTenant ? true : $tenant->hasPlanFeature('priority_support'),
                        'qr_booking' => $isPreviewTenant ? true : $tenant->hasPlanFeature('qr_booking'),
                        'multi_branch' => $isPreviewTenant ? true : $tenant->hasPlanFeature('multi_branch'),
                        'enhanced_reports' => $isPreviewTenant ? true : $tenant->hasPlanFeature('enhanced_reports'),
                        'custom_system_features' => $isPreviewTenant ? true : $tenant->hasPlanFeature('custom_system_features'),
                    ],
                    'feature_requirements' => \App\Models\SubscriptionPlan::getFeatureRequirementMap(),
                    'limits' => [
                        'max_users' => $isPreviewTenant ? null : $tenant->getPlanLimit('max_users'),
                        'max_patients' => $isPreviewTenant ? null : $tenant->getPlanLimit('max_patients'),
                        'max_appointments' => $isPreviewTenant ? null : $tenant->getPlanLimit('max_appointments'),
                    ],
                    'current_usage' => [
                        'users' => \App\Models\User::count(),
                        'patients' => \App\Models\Patient::count(),
                        'appointments' => \App\Models\Appointment::count(),
                    ],
                    // Global mapping of all feature keys to their implementation statuses
                    'global_feature_statuses' => \App\Models\Feature::pluck('implementation_status', 'key'),
                ];
        },
            'pending_updates_count' => function () use ($request) {
            if (!$request->user() || !tenant())
                return 0;

            // Only notify those who can manage settings (Owners/Admins)
            // This prevents spamming doctors/staff who can't apply updates anyway
            if (!$request->user()->can('manage settings') && !$request->user()->hasRole('Owner')) {
                return 0;
            }

            $cacheKey = "tenant_" . tenant()->id . "_pending_updates_count";

            return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHour(), function () {
                    return \App\Models\TenantFeatureUpdate::where('tenant_id', tenant()->id)
                        ->pending()
                        ->count();
                }
                );
            },
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'preview_mode' => function () use ($request) {
            $tenant = tenant();
            $preview = $request->session()->get('tenant_preview_active');

            if (!$tenant) {
                return [
                        'active' => false,
                    ];
            }

            $previewTenantId = (string)config('tenancy.preview.tenant_id', 'preview-sandbox');
            if ((string)$tenant->getTenantKey() === $previewTenantId) {
                return [
                        'active' => true,
                        'started_at' => $preview['started_at'] ?? null,
                        'subdomain' => (string)config('tenancy.preview.subdomain', 'tenantpreview'),
                    ];
            }

            if (!is_array($preview)) {
                return [
                        'active' => false,
                    ];
            }

            $matchesTenant = (string)($preview['tenant_id'] ?? '') === (string)$tenant->getTenantKey();

            return [
                    'active' => $matchesTenant && (($preview['active'] ?? false) === true),
                    'started_at' => $preview['started_at'] ?? null,
                    'subdomain' => $preview['subdomain'] ?? null,
                ];
        },
            'admin_preview_mode' => function () use ($request) {
            $preview = $request->session()->get('tenant_preview_active');

            if (!is_array($preview)) {
                return [
                        'active' => false,
                    ];
            }

            return [
                    'active' => (($preview['active'] ?? false) === true),
                    'tenant_id' => $preview['tenant_id'] ?? null,
                    'subdomain' => $preview['subdomain'] ?? null,
                    'is_isolated' => (($preview['is_isolated'] ?? false) === true),
                    'started_at' => $preview['started_at'] ?? null,
                ];
        },
            'preview_credentials' => function () use ($request) {
            if (tenant() || !$request->user()) {
                return null;
            }

            $user = $request->user();
            $isAdmin = (bool)($user->getAttribute('is_admin') ?? false)
                || (method_exists($user, 'hasRole') && ($user->hasRole('Admin') || $user->hasRole('System Root')));

            if (!$isAdmin) {
                return null;
            }

            return [
                    'email' => (string)config('tenancy.preview.login_email', 'preview-owner@local.test'),
                    'password' => (string)config('tenancy.preview.login_password', 'preview-owner-password'),
                    'subdomain' => (string)config('tenancy.preview.subdomain', 'tenantpreview'),
                ];
        },
            'branding' => function () {
            $tenant = tenant();

            // Central Admin: use SystemSetting values only
            if (!$tenant) {
                return [
                        'platform_name' => SystemSetting::get('platform_name', 'DCMS'),
                        'platform_logo' => SystemSetting::get('platform_logo'),
                        'primary_color' => SystemSetting::get('primary_color', '#0ea5e9'),
                        'footer_text' => SystemSetting::get('footer_text', '© 2026 DCMS. All rights reserved.'),
                        'sidebar_position' => SystemSetting::get('sidebar_position', 'left'),
                    ];
            }

            // Tenant: use TenantBrandingService as absolute source for specific keys
            $branding = \App\Services\TenantBrandingService::getAll();

            return [
                    'platform_name' => $branding['clinic_name'] ?? $tenant->name ?? SystemSetting::get('platform_name', 'DCMS'),
                    'platform_logo' => $branding['logo_base64'] ?? $tenant->logo_path ?? SystemSetting::get('platform_logo'),
                    'primary_color' => $branding['primary_color'] ?? $tenant->branding_color ?? SystemSetting::get('primary_color', '#0ea5e9'),
                    'footer_text' => SystemSetting::get('footer_text', '© 2026 DCMS. All rights reserved.'),
                    'sidebar_position' => SystemSetting::get('sidebar_position', 'left'),
                ];
        },
            'branding_computed' => function () {
            $tenant = tenant();

            // Central Admin: compute from SystemSetting only
            if (!$tenant) {
                $color = SystemSetting::get('primary_color', '#0ea5e9');
                $hex = ltrim($color, '#');
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
                return [
                        'primary_color' => $color,
                        'contrast_color' => $luminance > 0.5 ? '#1f2937' : '#ffffff',
                        'source' => 'central',
                    ];
            }

            // Tenant: compute from TenantBrandingService (untouched)
            $branding = \App\Services\TenantBrandingService::findMany(['primary_color']);

            $centralColor = SystemSetting::get('primary_color', '#0ea5e9');
            $tenantColor = $branding['primary_color'] ?? $tenant->branding_color ?? null;

            $activeColor = $tenantColor ?: $centralColor;

            // Server-side luminance calculation for contrast color
            $hex = ltrim($activeColor, '#');
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
            $contrastColor = $luminance > 0.5 ? '#1f2937' : '#ffffff';

            return [
                    'primary_color' => $activeColor,
                    'contrast_color' => $contrastColor,
                    'source' => $tenantColor ? 'tenant' : 'central',
                ];
        },
        ];
    }
}
