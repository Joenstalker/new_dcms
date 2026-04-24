<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Identification Middleware
 * 
 * Extracts the {tenant} slug from the URL, verifies it exists in the database,
 * and aborts with a 404 if the clinic doesn't exist.
 * 
 * This middleware should be applied to all tenant subdomain routes.
 */
class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the tenant subdomain from the route parameters
        $tenantSlug = $request->route('tenant');
        
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', ['lvh.me', 'localhost', '127.0.0.1']);

        // IF THE HOST IS A CENTRAL DOMAIN, PASS THROUGH IMMEDIATELY
        if (in_array($host, $centralDomains)) {
            return $next($request);
        }

        // If no tenant parameter found, try to extract from hostname
        if (empty($tenantSlug)) {
            $parts = explode('.', $host);

            // Check if this is a subdomain request (more than 2 parts: subdomain.domain.tld)
            if (count($parts) >= 3) {
                // The first part is the tenant subdomain
                $tenantSlug = $parts[0];
            }
        }

        // If still no tenant found, let the request pass through
        if (empty($tenantSlug)) {
            return $next($request);
        }

        // Validate tenant slug format
        if (!$this->isValidSlug($tenantSlug)) {
            abort(404, 'Invalid tenant identifier');
        }

        // Look up the tenant in the database
        $domain = $this->findTenantDomain($tenantSlug);

        // If tenant not found, abort with 404
        if (!$domain) {
            abort(404, 'Clinic not found. The clinic subdomain you are looking for does not exist.');
        }

        // Get the tenant and check if it's active
        $tenant = $domain->tenant;

        // Check if tenant exists and is not disabled
        if (!$tenant) {
            abort(404, 'Clinic not found');
        }

        // Check if tenant has the 'status' attribute and if it's inactive
        if (isset($tenant->status) && $tenant->status === 'inactive') {
            abort(403, 'This clinic account has been deactivated. Please contact support.');
        }

        // Initialize tenancy for this request
        // This sets up the database connection and other tenant-specific configurations
        tenancy()->initialize($tenant);

        // Make tenant available to controllers/views
        $request->merge(['tenant' => $tenant]);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_domain', $domain);

        return $next($request);
    }

    /**
     * Validate tenant slug format
     * 
     * @param string $slug
     * @return bool
     */
    private function isValidSlug(string $slug): bool
    {
        // Subdomain must be 3-63 characters, alphanumeric with hyphens allowed
        // Must start and end with alphanumeric character
        return preg_match('/^[a-z0-9]([a-z0-9-]{1,61}[a-z0-9])?$/', $slug) === 1;
    }

    /**
     * Find tenant domain in the database
     * 
     * @param string $slug
     * @return \Stancl\Tenancy\Database\Models\Domain|null
     */
    private function findTenantDomain(string $slug): ?Domain
    {
        // First try exact match (case-insensitive)
        $domain = Domain::where('domain', $slug)
            ->orWhere('domain', strtolower($slug))
            ->first();

        // If not found, try partial match for edge cases
        if (!$domain) {
            $domain = Domain::where('domain', 'like', $slug . '.%')->first();
        }

        return $domain;
    }
}
