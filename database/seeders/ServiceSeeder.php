<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\User;
use Database\Factories\ServiceFactory;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Standard stancl/tenancy check
        $tenant = tenant();

        if (!$tenant) {
            // Wait, maybe it's called manually and manually bound? We can do the user's fallback
            $tenant = app()->bound('tenant') ? app('tenant') : \App\Models\Tenant::first();
        }

        if (!$tenant) {
            if ($this->command) {
                $this->command->error('No tenant found. Please create a tenant first or bind one to the container.');
            }
            return;
        }

        if ($this->command) {
            $tenantName = $tenant->name ?? 'Unknown';
            $tenantId = $tenant->id ?? $tenant->getTenantKey();
            $this->command->info("Seeding services for tenant: {$tenantName} ({$tenantId})");
        }

        // We must fetch an existing user in the tenant database to assign as the creator.
        $owner = User::role('Owner')->first() ?? User::first();
        if (!$owner && $this->command) {
            $this->command->warn("No users found to set as created_by/approved_by! Creating a dummy user is not recommended, skipping services.");
            return;
        }
        $ownerId = $owner ? $owner->id : 1;

        $services = ServiceFactory::defaultCatalog();

        foreach ($services as $service) {
            Service::updateOrCreate(
            [
                // Found by name
                'name' => $service['name'],
            ],
            Service::factory()
                ->make([
                    'name' => $service['name'],
                    // Mapped to actual schema (category is embedded in description).
                    'description' => $service['name'] . ' procedure | ' . $service['category'],
                    'price' => $service['amount'],
                    'created_by' => $ownerId,
                ])
                ->toArray()
            );
        }

        if ($this->command) {
            $this->command->info('Services seeded successfully.');
        }
    }
}
