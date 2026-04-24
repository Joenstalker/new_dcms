<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\ReleaseService;
use App\Services\TenantUpgradeRolloutService;
use App\Services\TenantVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VersionComplianceController extends Controller
{
    public function index(
        ReleaseService $releaseService,
        TenantVersionService $tenantVersionService,
        TenantUpgradeRolloutService $rolloutService,
    ): Response {
        $latestRelease = $releaseService->latestRelease();
        $globalVersion = $latestRelease?->version ?? $releaseService->currentVersion();

        $tenants = Tenant::query()
            ->with('domains')
            ->orderBy('name')
            ->get()
            ->map(function (Tenant $tenant) use ($tenantVersionService, $globalVersion) {
                $tenantVersion = $tenant->version;

                $status = 'unknown';
                if ($tenantVersion) {
                    $comparison = $tenantVersionService->compareVersions($tenantVersion, $globalVersion);
                    $status = $comparison === 0
                        ? 'up_to_date'
                        : ($comparison < 0 ? 'outdated' : 'ahead');
                }

                return [
                    'id' => (string) $tenant->id,
                    'name' => $tenant->name ?: (string) $tenant->id,
                    'status' => $tenant->status,
                    'tenant_version' => $tenantVersion,
                    'global_version' => $globalVersion,
                    'compliance_status' => $status,
                    'domain' => $tenant->domains->first()?->domain,
                    'last_updated_at' => optional($tenant->updated_at)?->toIso8601String(),
                ];
            })
            ->values();

        $summary = [
            'total_tenants' => $tenants->count(),
            'up_to_date' => $tenants->where('compliance_status', 'up_to_date')->count(),
            'outdated' => $tenants->where('compliance_status', 'outdated')->count(),
            'unknown' => $tenants->where('compliance_status', 'unknown')->count(),
            'ahead' => $tenants->where('compliance_status', 'ahead')->count(),
        ];

        return Inertia::render('Admin/VersionCompliance/Index', [
            'summary' => $summary,
            'globalVersion' => $globalVersion,
            'latestRelease' => $latestRelease ? [
                'version' => $latestRelease->version,
                'released_at' => optional($latestRelease->released_at)?->toIso8601String(),
                'requires_db_update' => (bool) $latestRelease->requires_db_update,
            ] : null,
            'tenants' => $tenants,
            'autoRolloutEnabled' => $rolloutService->isAutoRolloutEnabled(),
            'lastRollout' => $rolloutService->getLastRolloutMeta(),
        ]);
    }

    public function toggleAutoRollout(Request $request, TenantUpgradeRolloutService $rolloutService): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $rolloutService->setAutoRolloutEnabled((bool) $validated['enabled']);

        return back()->with('success', 'Automatic tenant upgrade rollout setting updated.');
    }

    public function triggerRollout(TenantUpgradeRolloutService $rolloutService): RedirectResponse
    {
        $batch = $rolloutService->dispatchRollout();

        if (! $batch) {
            return back()->with('info', 'No outdated tenants found. Rollout not required.');
        }

        return back()->with('success', "Tenant upgrade rollout dispatched (Batch ID: {$batch->id}).");
    }

    public function syncWithGitHub(ReleaseService $releaseService): RedirectResponse
    {
        $release = $releaseService->syncLatestRelease();

        if (! $release) {
            return back()->with('error', 'Failed to sync latest release from GitHub. Please check your GitHub configuration.');
        }

        return back()->with('success', "Latest version synced from GitHub: {$release->version}");
    }
}
