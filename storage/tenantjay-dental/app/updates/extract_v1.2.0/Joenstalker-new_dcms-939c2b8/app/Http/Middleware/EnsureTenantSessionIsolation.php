<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSessionIsolation
{
    /**
     * Prevent central/admin web sessions from leaking into tenant auth context.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!tenant()) {
            return $next($request);
        }

        if (!$request->user()) {
            return $next($request);
        }

        if ($this->isValidPreviewRequest($request)) {
            return $next($request);
        }

        $tenantId = (string) tenant()->getTenantKey();
        $sessionTenantId = (string) $request->session()->get('tenant_authenticated_tenant_id', '');
        $sessionUserId = (int) $request->session()->get('tenant_authenticated_user_id', 0);
        $hasTenantAuthFlag = (bool) $request->session()->get('tenant_authenticated', false);

        $currentUserId = (int) optional($request->user())->id;

        $isValidTenantSession = $hasTenantAuthFlag
            && $sessionTenantId === $tenantId
            && $sessionUserId === $currentUserId;

        if (!$isValidTenantSession) {
            Auth::guard('web')->logout();
            $request->session()->forget([
                'tenant_authenticated',
                'tenant_authenticated_tenant_id',
                'tenant_authenticated_user_id',
            ]);
        }

        return $next($request);
    }

    private function isValidPreviewRequest(Request $request): bool
    {
        $preview = $request->session()->get('tenant_preview_active');

        if (!is_array($preview) || (($preview['active'] ?? false) !== true)) {
            return false;
        }

        return (string) ($preview['tenant_id'] ?? '') === (string) tenant()->getTenantKey();
    }
}
