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
                    'roles' => $request->user()->getRoleNames()->toArray(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
                ] : null,
            ],
            'config' => [
                'central_domain' => config('tenancy.central_domains.0', 'localhost'),
                'app_url' => $request->getSchemeAndHttpHost(),
                'recaptcha_site_key' => config('services.recaptcha.site_key', ''),
            ],
            'tenant' => tenant(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            // Branding settings shared globally
            'branding' => $this->getBrandingSettings(),
        ];
    }

    /**
     * Get branding settings from the database.
     */
    private function getBrandingSettings(): array
    {
        return [
            'platform_name' => SystemSetting::get('platform_name', 'DCMS'),
            'platform_logo' => SystemSetting::get('platform_logo'),
            'primary_color' => SystemSetting::get('primary_color', '#0ea5e9'),
            'footer_text' => SystemSetting::get('footer_text', '© 2026 DCMS. All rights reserved.'),
            'sidebar_position' => SystemSetting::get('sidebar_position', 'left'),
        ];
    }
}
