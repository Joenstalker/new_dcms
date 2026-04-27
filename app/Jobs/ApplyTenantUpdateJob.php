<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ApplyTenantUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $tenantId,
        private readonly array $featureIds
    ) {}

    public function handle(): void
    {
        $exitCode = Artisan::call('system:apply-tenant-update', [
            'tenant_id' => $this->tenantId,
            'feature_ids' => $this->featureIds,
        ]);

        if ($exitCode !== 0) {
            Log::error('ApplyTenantUpdateJob failed.', [
                'tenant_id' => $this->tenantId,
                'feature_ids' => $this->featureIds,
                'exit_code' => $exitCode,
            ]);
        }
    }
}
