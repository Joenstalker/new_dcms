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

class SeedTenantDatabase implements ShouldQueue{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant
    ) {
        // 
    }

    public function handle(): void
    {
        try {
            tenancy()->initialize($this->tenant);

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
