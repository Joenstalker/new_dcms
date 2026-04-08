<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ImpersonateTenantPreviewUser
{
    /**
     * Hydrate auth user for tenant preview requests without replacing admin login session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (!$tenant) {
            return $next($request);
        }

        $preview = $request->session()->get('tenant_preview_active');

        $isValidPreview = is_array($preview)
            && (($preview['active'] ?? false) === true)
            && (string)($preview['tenant_id'] ?? '') === (string)$tenant->getTenantKey()
            && !empty($preview['tenant_user_id']);

        if ($isValidPreview) {
            Auth::onceUsingId((int)$preview['tenant_user_id']);
        }

        return $next($request);
    }
}
