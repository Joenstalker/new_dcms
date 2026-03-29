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
        return url('tenant-storage/' . ltrim($path, '/'));
    }
}
