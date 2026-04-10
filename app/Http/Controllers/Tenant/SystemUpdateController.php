<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Jobs\UpgradeTenantJob;
use App\Services\ReleaseService;
use App\Services\TenantVersionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class SystemUpdateController extends Controller
{
    use ApiResponse;

    protected ReleaseService $releaseService;

    protected TenantVersionService $tenantVersionService;

    public function __construct(ReleaseService $releaseService, TenantVersionService $tenantVersionService)
    {
        $this->releaseService = $releaseService;
        $this->tenantVersionService = $tenantVersionService;
    }

    /**
     * GET /api/system/update-status
     * Returns the current version, the latest baseline configuration and flags
     * if the individual tenant schema requires a migration sync.
     */
    public function getStatus(): JsonResponse
    {
        $tenant = tenant();
        $latest = $this->releaseService->latestRelease();

        $currentVersion = $tenant->version ?? 'unknown';
        $latestVersion = $latest ? $latest->version : $this->releaseService->currentVersion();

        $requiresUpdate = $this->tenantVersionService->needsUpgrade($tenant);

        return $this->respondSuccess([
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'requires_update' => $requiresUpdate,
            'release_notes' => $latest ? $latest->release_notes : null,
        ], 'System update status retrieved successfully.');
    }

    /**
     * POST /api/system/update
     * Pushes the UpgradeTenantJob explicitly to the async queue mechanism
     * without blocking the standard request lifecycle.
     */
    public function update(): JsonResponse
    {
        $tenant = tenant();

        if (! $this->tenantVersionService->needsUpgrade($tenant)) {
            return $this->respondError('Your system is already up to date.', 400);
        }

        // Trigger UpgradeTenantJob entirely asynchronously.
        // Doing this ensures the tenant's HTTP cycle behaves at O(1).
        UpgradeTenantJob::dispatch($tenant);

        return $this->respondSuccess(
            null,
            'System update has been queued. The backend will be migrated shortly.'
        );
    }
}
