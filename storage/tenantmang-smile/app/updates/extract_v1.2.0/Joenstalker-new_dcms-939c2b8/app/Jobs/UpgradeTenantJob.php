<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\TenantUpgradeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpgradeTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tenant;

    // Limits the concurrency strictly to avoid DB thrashing
    public $tries = 3;
    public $timeout = 300; // 5 minutes max execution per tenant schema update

    /**
     * Create a new job instance.
     */
    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Execute the job safely using the TenantUpgradeService engine
     */
    public function handle(TenantUpgradeService $upgradeService): void
    {
        Log::info("Starting isolated upgrade job for tenant DB: {$this->tenant->id}");

        try {
            $upgradeService->upgrade($this->tenant);
            Log::info("Successfully isolated upgraded tenant DB: {$this->tenant->id}");
        }
        catch (\Exception $e) {
            Log::error("Schema upgrade job hard-failed for tenant: {$this->tenant->id}");
            // Propagate the exception specifically so the queue worker retries or fails the payload
            throw $e;
        }
    }
}
