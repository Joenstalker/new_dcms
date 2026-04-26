<?php

namespace App\Services;

use App\Models\Tenant;

class TenantVersionService
{
    protected ReleaseService $releaseService;

    public function __construct(ReleaseService $releaseService)
    {
        $this->releaseService = $releaseService;
    }

    /**
     * Check if a specific tenant needs a database upgrade.
     */
    public function needsUpgrade(Tenant $tenant): bool
    {
        $globalVersion = $this->releaseService->currentVersion();
        $tenantVersion = $tenant->version;

        if (!$tenantVersion) {
            return true; // Missing version indicates upgrade or registry fixing required
        }

        // If the tenant version is less than global version, an upgrade is needed.
        return $this->compareVersions($tenantVersion, $globalVersion) < 0;
    }

    /**
     * Compare two version strings directly (e.g., v1.0.0 vs v1.1.0).
     * Returns -1 if $v1 < $v2, 0 if equal, 1 if $v1 > $v2.
     */
    public function compareVersions(string $v1, string $v2): int
    {
        // Safely strip the 'v' or 'V' prefixes if they exist
        $v1Clean = ltrim($v1, 'vV');
        $v2Clean = ltrim($v2, 'vV');

        return version_compare($v1Clean, $v2Clean);
    }
}
