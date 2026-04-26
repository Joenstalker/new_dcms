<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantStorageUsageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ReconcileTenantFileUsage extends Command
{
    protected $signature = 'tenants:reconcile-file-usage {--tenant_id= : Limit to a single tenant ID} {--disk=public : Disk to reconcile} {--prefix= : Optional prefix to scan within the disk} {--chunk=20 : Tenants per chunk}';

    protected $description = 'Reconciles tenant file usage by scanning the tenant filesystem and rebuilding the central tenant_file_objects ledger.';

    public function handle(TenantStorageUsageService $usage): int
    {
        $tenantId = (string) ($this->option('tenant_id') ?? '');
        $disk = (string) ($this->option('disk') ?? 'public');
        $prefix = $this->option('prefix');
        $chunk = max(1, (int) $this->option('chunk'));

        $query = Tenant::query();
        if ($tenantId !== '') {
            $query->where('id', $tenantId);
        }

        $total = $query->count();
        $this->info("Reconciling file usage for {$total} tenant(s) on disk '{$disk}'...");

        $tenantAwareDisks = (array) (config('tenancy.filesystem.disks') ?? []);
        $mustRunInTenantContext = in_array($disk, $tenantAwareDisks, true);

        $processed = 0;

        $query->orderBy('id')->chunk($chunk, function (Collection $tenants) use ($usage, $disk, $prefix, $mustRunInTenantContext, &$processed) {
            foreach ($tenants as $tenant) {
                /** @var Tenant $tenant */
                try {
                    $tenantKey = (string) $tenant->getTenantKey();

                    if ($mustRunInTenantContext) {
                        $tenant->run(function () use ($usage, $tenantKey, $disk, $prefix) {
                            $usage->reconcileTenantDisk($tenantKey, $disk, is_string($prefix) ? $prefix : null);
                        });
                    } else {
                        $usage->reconcileTenantDisk($tenantKey, $disk, is_string($prefix) ? $prefix : null);
                    }

                    $processed++;
                } catch (\Throwable $e) {
                    Log::warning('Failed to reconcile tenant file usage', [
                        'tenant_id' => (string) $tenant->id,
                        'disk' => $disk,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->info("Done. Reconciled {$processed} tenant(s).");

        return Command::SUCCESS;
    }
}

