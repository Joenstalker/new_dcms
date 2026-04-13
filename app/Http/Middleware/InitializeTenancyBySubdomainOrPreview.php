<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyBySubdomainOrPreview
{
    /**
     * Initialize tenancy either from subdomain (normal flow) or from admin preview session on central domain.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (tenant()) {
            Log::info('Tenancy already initialized', ['tenant_id' => tenant()->id]);
            return $next($request);
        }

        $host = $request->getHost();
        $centralDomains = array_filter((array)config('tenancy.central_domains', []));
        $isCentralDomain = in_array($host, $centralDomains, true);

        Log::info('InitializeTenancyBySubdomainOrPreview middleware', [
            'host' => $host,
            'is_central_domain' => $isCentralDomain,
            'has_tenant' => tenant() !== null,
        ]);

        if ($this->shouldInitializePreviewTenancy($request)) {
            $preview = $request->session()->get('tenant_preview_active');
            $previewTenantId = (string)($preview['tenant_id'] ?? '');
            $tenant = Tenant::find($previewTenantId);

            if ($tenant) {
                Log::info('Initializing preview tenancy', ['tenant_id' => $previewTenantId]);
                tenancy()->initialize($tenant);
                return $next($request);
            }

            $request->session()->forget('tenant_preview_active');
            $request->session()->forget('tenant_preview_bootstrap');
        }

        // On central domains, never call subdomain tenancy middleware because it throws NotASubdomain.
        if ($isCentralDomain) {
            Log::warning('Tenant preview access attempted on central domain without an active preview session.', [
                'path' => $request->path(),
                'host' => $host,
                'user_id' => optional($request->user())->id,
            ]);

            return redirect()->route('admin.dashboard')
                ->with('error', 'Tenant preview session is not active. Use Switch User -> Start Tenant Preview.');
        }

        Log::info('Delegating to InitializeTenancyBySubdomain', ['host' => $host]);
        return app(InitializeTenancyBySubdomain::class)->handle($request, $next);
    }

    private function shouldInitializePreviewTenancy(Request $request): bool
    {
        $preview = $request->session()->get('tenant_preview_active');
        if (!is_array($preview) || (($preview['active'] ?? false) !== true)) {
            return false;
        }

        $host = $request->getHost();
        $centralDomains = array_filter((array)config('tenancy.central_domains', []));
        if (!in_array($host, $centralDomains, true)) {
            return false;
        }

        $enabledSettingKey = (string)config('tenancy.preview.enabled_setting_key', 'admin_tenant_preview_enabled');
        $previewEnabled = (bool)SystemSetting::get($enabledSettingKey, true);
        if (!$previewEnabled) {
            return false;
        }

        $user = $request->user();
        if (!$user) {
            return false;
        }

        $isAdminFlag = (bool)($user->getAttribute('is_admin') ?? false);
        $hasAdminRole = method_exists($user, 'hasRole')
            ? ($user->hasRole('Admin') || $user->hasRole('System Root'))
            : false;

        return $isAdminFlag || $hasAdminRole;
    }
}
