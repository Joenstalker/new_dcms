<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Symfony\Component\HttpFoundation\Response;

class PreventAccessFromCentralDomainsOrPreview
{
    /**
     * Allow tenant routes on central domains only for active admin preview sessions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isCentralDomainPreviewRequest($request)) {
            return $next($request);
        }

        return app(PreventAccessFromCentralDomains::class)->handle($request, $next);
    }

    private function isCentralDomainPreviewRequest(Request $request): bool
    {
        if (!tenant()) {
            return false;
        }

        $host = $request->getHost();
        $centralDomains = array_filter((array)config('tenancy.central_domains', []));
        if (!in_array($host, $centralDomains, true)) {
            return false;
        }

        $preview = $request->session()->get('tenant_preview_active');
        if (!is_array($preview) || (($preview['active'] ?? false) !== true)) {
            return false;
        }

        return (string)($preview['tenant_id'] ?? '') === (string)tenant()->getTenantKey();
    }
}
