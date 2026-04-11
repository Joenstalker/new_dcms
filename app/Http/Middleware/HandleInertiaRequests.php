<?php

namespace App\Http\Middleware;

use App\Models\Appointment;
use App\Models\Feature;
use App\Models\Patient;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\TenantFeatureUpdate;
use App\Models\TenantNotification;
use App\Models\User;
use App\Services\AppVersionService;
use App\Services\TenantBrandingService;
use Detection\MobileDetect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

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
        $detect = new MobileDetect;
        $resolvedUser = $request->user();

        if (! tenant() && $resolvedUser) {
            $isAdminFlag = (bool) ($resolvedUser->getAttribute('is_admin') ?? false);
            $hasAdminRole = method_exists($resolvedUser, 'hasRole')
                ? ($resolvedUser->hasRole('Admin') || $resolvedUser->hasRole('System Root'))
                : false;

            if (! $isAdminFlag && ! $hasAdminRole) {
                Auth::guard('web')->logout();
                $request->session()->forget([
                    'tenant_authenticated',
                    'tenant_authenticated_tenant_id',
                    'tenant_authenticated_user_id',
                ]);
                $resolvedUser = null;
            }
        }

        if (tenant() && $resolvedUser) {
            $preview = $request->session()->get('tenant_preview_active');
            $isValidPreview = is_array($preview)
                && (($preview['active'] ?? false) === true)
                && (string) ($preview['tenant_id'] ?? '') === (string) tenant()->getTenantKey();

            if ($isValidPreview) {
                $previewUserId = (int) ($preview['tenant_user_id'] ?? 0);
                if ($previewUserId > 0) {
                    $previewUser = User::query()->find($previewUserId);
                    if ($previewUser) {
                        $resolvedUser = $previewUser;
                    }
                }
            }

            if (! $isValidPreview) {
                $tenantId = (string) tenant()->getTenantKey();
                $sessionTenantId = (string) $request->session()->get('tenant_authenticated_tenant_id', '');
                $sessionUserId = (int) $request->session()->get('tenant_authenticated_user_id', 0);
                $hasTenantAuthFlag = (bool) $request->session()->get('tenant_authenticated', false);
                $currentUserId = (int) $resolvedUser->id;

                $isValidTenantSession = $hasTenantAuthFlag
                    && $sessionTenantId === $tenantId
                    && $sessionUserId === $currentUserId;

                if (! $isValidTenantSession) {
                    Auth::guard('web')->logout();
                    $request->session()->forget([
                        'tenant_authenticated',
                        'tenant_authenticated_tenant_id',
                        'tenant_authenticated_user_id',
                    ]);
                    $resolvedUser = null;
                }
            }
        }

        return [
            ...parent::share($request),
            'device' => [
                'isMobile' => $detect->isMobile(),
                'isTablet' => $detect->isTablet(),
                'isDesktop' => ! $detect->isMobile() && ! $detect->isTablet(),
            ],
            'auth' => [
                'user' => $resolvedUser ? [
                    'id' => $resolvedUser->id,
                    'name' => $resolvedUser->name,
                    'email' => $resolvedUser->email,
                    'profile_picture_url' => $resolvedUser->profile_picture_url,
                    'roles' => $resolvedUser->getRoleNames()->toArray(),
                    'permissions' => $resolvedUser->getAllPermissions()->pluck('name')->toArray(),
                    'requires_password_change' => (bool) $resolvedUser->requires_password_change,
                ] : null,
            ],
            'config' => [
                'central_domain' => parse_url((string) config('app.url', ''), PHP_URL_HOST)
                    ?: config('tenancy.central_domains.0', 'localhost'),
                'app_url' => $request->getSchemeAndHttpHost(),
                'recaptcha_site_key' => config('services.recaptcha.site_key', ''),
                'version' => fn () => tenant() ? (tenant()->version ?: 'v1.0.0') : AppVersionService::getVersion(),
            ],
            'tenant' => function () {
                $tenant = tenant();
                if (! $tenant) {
                    return null;
                }

                // Optimized: Only fetch what's needed for the layout/gating
                // Priority: TenantBrandingService (Binary/Custom) > Tenant Model (Legacy/Sync)
                $branding = TenantBrandingService::getAll();

                $enabledFeatures = $tenant->getResolvedEnabledFeaturesForUi($branding);

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
                    'portal_background_type' => $branding['portal_background_type'] ?? 'color',
                    'portal_background_color' => $branding['portal_background_color'] ?? null,
                    'portal_background_image' => $branding['portal_background_image'] ?? null,
                    'is_premium' => $tenant->canCustomizeBranding(),
                ]);
            },
            'tenant_plan' => function () {
                $tenant = tenant();
                if (! $tenant) {
                    return null;
                }

                $previewTenantId = (string) config('tenancy.preview.tenant_id', 'preview-sandbox');
                $isPreviewTenant = (string) $tenant->getTenantKey() === $previewTenantId;

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
                    'feature_requirements' => SubscriptionPlan::getFeatureRequirementMap(),
                    'limits' => [
                        'max_users' => $isPreviewTenant ? null : $tenant->getPlanLimit('max_users'),
                        'max_patients' => $isPreviewTenant ? null : $tenant->getPlanLimit('max_patients'),
                        'max_appointments' => $isPreviewTenant ? null : $tenant->getPlanLimit('max_appointments'),
                    ],
                    'current_usage' => [
                        'users' => User::count(),
                        'patients' => Patient::count(),
                        'appointments' => Appointment::count(),
                    ],
                    // Global mapping of all feature keys to their implementation statuses
                    'global_feature_statuses' => Feature::pluck('implementation_status', 'key'),
                ];
            },
            'pending_updates_count' => function () use ($request) {
                if (! $request->user() || ! tenant()) {
                    return 0;
                }

                // Notify only users who can manage updates (or owners)
                if (! $request->user()->can('manage system updates') && ! $request->user()->hasRole('Owner')) {
                    return 0;
                }

                $cacheKey = 'tenant_'.tenant()->id.'_pending_updates_count';

                return Cache::remember($cacheKey, now()->addHour(), function () {
                    return TenantFeatureUpdate::where('tenant_id', tenant()->id)
                        ->pending()
                        ->count();
                }
                );
            },
            'sidebar_badges' => function () use ($request) {
                if (! $request->user() || ! tenant()) {
                    return null;
                }

                $user = $request->user();

                return [
                    'appointments_pending' => $user->can('view appointments')
                        ? Appointment::where('status', 'pending')->count()
                        : 0,
                    'notifications_unread' => TenantNotification::where('user_id', $user->id)
                        ->unread()
                        ->count(),
                ];
            },
            'flash' => [
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                ],
            'preview_mode' => function () use ($request) {
                $tenant = tenant();
                $preview = $request->session()->get('tenant_preview_active');

                if (! $tenant) {
                    return [
                        'active' => false,
                    ];
                }

                $previewTenantId = (string) config('tenancy.preview.tenant_id', 'preview-sandbox');
                if ((string) $tenant->getTenantKey() === $previewTenantId) {
                    return [
                        'active' => true,
                        'started_at' => $preview['started_at'] ?? null,
                        'subdomain' => (string) config('tenancy.preview.subdomain', 'tenantpreview'),
                    ];
                }

                if (! is_array($preview)) {
                    return [
                        'active' => false,
                    ];
                }

                $matchesTenant = (string) ($preview['tenant_id'] ?? '') === (string) $tenant->getTenantKey();

                return [
                    'active' => $matchesTenant && (($preview['active'] ?? false) === true),
                    'started_at' => $preview['started_at'] ?? null,
                    'subdomain' => $preview['subdomain'] ?? null,
                ];
            },
            'admin_preview_mode' => function () use ($request) {
                $preview = $request->session()->get('tenant_preview_active');

                if (! is_array($preview)) {
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
                if (tenant() || ! $request->user()) {
                    return null;
                }

                $user = $request->user();
                $isAdmin = (bool) ($user->getAttribute('is_admin') ?? false)
                    || (method_exists($user, 'hasRole') && ($user->hasRole('Admin') || $user->hasRole('System Root')));

                if (! $isAdmin) {
                    return null;
                }

                return [
                    'email' => (string) config('tenancy.preview.login_email', 'preview-owner@local.test'),
                    'password' => (string) config('tenancy.preview.login_password', 'preview-owner-password'),
                    'subdomain' => (string) config('tenancy.preview.subdomain', 'tenantpreview'),
                ];
            },
            'branding' => function () {
                $tenant = tenant();

                // Central Admin: use SystemSetting values only
                if (! $tenant) {
                    return [
                        'platform_name' => SystemSetting::get('platform_name', 'DCMS'),
                        'platform_logo' => SystemSetting::get('platform_logo'),
                        'primary_color' => SystemSetting::get('primary_color', '#0ea5e9'),
                        'support_chat_bottom_offset' => (int) SystemSetting::get('support_chat_bottom_offset', 56),
                        'footer_text' => SystemSetting::get('footer_text', '© 2026 DCMS. All rights reserved.'),
                        'sidebar_position' => SystemSetting::get('sidebar_position', 'left'),
                    ];
                }

                // Tenant: use TenantBrandingService as absolute source for specific keys
                $branding = TenantBrandingService::getAll();

                return [
                    'platform_name' => $branding['clinic_name'] ?? $tenant->name ?? SystemSetting::get('platform_name', 'DCMS'),
                    'platform_logo' => $branding['logo_base64'] ?? $tenant->logo_path ?? SystemSetting::get('platform_logo'),
                    'primary_color' => $branding['primary_color'] ?? $tenant->branding_color ?? SystemSetting::get('primary_color', '#0ea5e9'),
                    'support_chat_bottom_offset' => (int) ($branding['support_chat_bottom_offset'] ?? SystemSetting::get('support_chat_bottom_offset', 56)),
                    'footer_text' => SystemSetting::get('footer_text', '© 2026 DCMS. All rights reserved.'),
                    'sidebar_position' => SystemSetting::get('sidebar_position', 'left'),
                ];
            },
            'branding_computed' => function () {
                $tenant = tenant();

                // Central Admin: compute from SystemSetting only
                if (! $tenant) {
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
                $branding = TenantBrandingService::findMany(['primary_color']);

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
