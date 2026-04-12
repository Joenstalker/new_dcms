<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures Stancl tenancy is bootstrapped before /broadcasting/auth runs on tenant hosts.
 *
 * Tenant routes already use InitializeTenancyBySubdomainOrPreview; that route is registered
 * globally with only the "web" stack, so without this middleware the default DB stays
 * central, SupportTicket lookups fail, and private channel authorization returns 403.
 */
class InitializeTenancyForBroadcastingAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (tenant()) {
            return $next($request);
        }

        $host = $request->getHost();
        $centralDomains = array_filter((array) config('tenancy.central_domains', []));

        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        return app(InitializeTenancyBySubdomain::class)->handle($request, $next);
    }
}
