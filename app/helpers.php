<?php

if (!function_exists('tenant_asset')) {
    /**
     * Generate a URL for a file in the current tenant's storage directory.
     *
     * Instead of using the public/storage symlink (which points to the
     * central storage), this generates a URL through the tenant-storage
     * controller route. The controller reads files from the tenant's
     * isolated storage directory.
     *
     * @param  string  $path  Relative path within the tenant's public storage
     * @return string
     */
    function tenant_asset(string $path): string
    {
        $path = ltrim($path, '/');

        // Relative URL so the browser always loads from the current document origin
        // (tenant subdomain). Absolute URLs built from APP_URL (e.g. http://localhost:8080)
        // break under CSP and trigger tenant resolution failures → ?error=clinic_not_found.
        return route('tenant.storage', ['path' => $path], absolute: false);
    }
}
