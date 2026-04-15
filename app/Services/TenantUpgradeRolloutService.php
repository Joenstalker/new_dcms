<?php

namespace App\Services;

use App\Jobs\UpgradeTenantJob;
use App\Models\SystemSetting;
use App\Models\Tenant;
use Illuminate\Bus\Batch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class TenantUpgradeRolloutService
{
    private const AUTO_ROLLOUT_KEY = 'auto_tenant_upgrade_rollout_enabled';

    public function __construct(
        private readonly TenantVersionService $tenantVersionService,
        private readonly ReleaseService $releaseService,
    ) {}

    public function isAutoRolloutEnabled(): bool
    {
        $this->ensureRolloutSettingExists();

        return (bool) SystemSetting::get(self::AUTO_ROLLOUT_KEY, false);
    }

    public function setAutoRolloutEnabled(bool $enabled): void
    {
        $this->ensureRolloutSettingExists();
        SystemSetting::set(self::AUTO_ROLLOUT_KEY, $enabled);
    }

    public function dispatchRollout(?Collection $tenants = null): ?Batch
    {
        $outdatedTenants = $tenants ?? $this->getOutdatedTenants();

        if ($outdatedTenants->isEmpty()) {
            return null;
        }

        $jobs = $outdatedTenants
            ->map(fn (Tenant $tenant) => new UpgradeTenantJob($tenant))
            ->values()
            ->all();

        $targetVersion = $this->releaseService->currentVersion();

        $batch = Bus::batch($jobs)
            ->name("Tenant Upgrade Rollout {$targetVersion}")
            ->dispatch();

        Cache::put('tenant_upgrade_rollout_last_batch_id', $batch->id, now()->addDays(7));
        Cache::put('tenant_upgrade_rollout_last_target_version', $targetVersion, now()->addDays(7));
        Cache::put('tenant_upgrade_rollout_last_total_jobs', count($jobs), now()->addDays(7));

        return $batch;
    }

    public function getOutdatedTenants(): Collection
    {
        return Tenant::query()
            ->orderBy('id')
            ->get()
            ->filter(fn (Tenant $tenant) => $this->tenantVersionService->needsUpgrade($tenant))
            ->values();
    }

    public function getLastRolloutMeta(): array
    {
        return [
            'batch_id' => Cache::get('tenant_upgrade_rollout_last_batch_id'),
            'target_version' => Cache::get('tenant_upgrade_rollout_last_target_version'),
            'total_jobs' => Cache::get('tenant_upgrade_rollout_last_total_jobs'),
        ];
    }

    private function ensureRolloutSettingExists(): void
    {
        $setting = SystemSetting::query()->firstOrCreate(
            ['key' => self::AUTO_ROLLOUT_KEY],
            [
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'maintenance',
                'description' => 'Automatically roll out tenant upgrades when new releases are detected.',
            ]
        );

        $metaUpdates = [];

        if ($setting->type !== 'boolean') {
            $metaUpdates['type'] = 'boolean';
        }

        if ($setting->group !== 'maintenance') {
            $metaUpdates['group'] = 'maintenance';
        }

        if ($setting->description !== 'Automatically roll out tenant upgrades when new releases are detected.') {
            $metaUpdates['description'] = 'Automatically roll out tenant upgrades when new releases are detected.';
        }

        if (!empty($metaUpdates)) {
            $setting->update($metaUpdates);
        }
    }
}
