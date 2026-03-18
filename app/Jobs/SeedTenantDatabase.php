<?php

namespace App\Jobs;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\Tenant;

class SeedTenantDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Tenant $tenant
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * This job is dispatched after a new tenant database has been migrated.
     * It seeds the tenant database with the required roles and permissions
     * so that the Owner role is available immediately after registration.
     */
    public function handle(): void
    {
        try {
            // Ensure we are in the tenant context
            tenancy()->initialize($this->tenant);

            // Run the roles and permissions seeder
            $seeder = new RolesAndPermissionsSeeder();
            $seeder->run();

            Log::info('Tenant database seeded successfully', [
                'tenant_id' => $this->tenant->getTenantKey(),
            ]);
        }
        catch (\Throwable $e) {
            Log::error('Failed to seed tenant database', [
                'tenant_id' => $this->tenant->getTenantKey(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
