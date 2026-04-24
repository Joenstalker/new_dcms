<?php

namespace App\Console\Commands;

use App\Models\PendingRegistration;
use App\Models\Tenant;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Database\Models\Domain;

class MigratePendingRegistrations extends Command
{
    protected $signature = ' tenants:migrate-pending';
    protected $description = 'Migrate existing pending registrations to tenants table';

    public function handle()
    {
        $this->info('Migrating pending registrations to tenants...');

        $pendingRegistrations = PendingRegistration::where('status', 'pending')
            ->orWhere('status', 'approved')
            ->get();

        $migrated = 0;
        $skipped = 0;

        foreach ($pendingRegistrations as $registration) {
            // Check if tenant already exists
            if (Tenant::where('id', $registration->subdomain)->exists()) {
                $this->warn("Tenant already exists for: {$registration->clinic_name} ({$registration->subdomain})");
                $skipped++;
                continue;
            }

            try {
                DB::beginTransaction();

                // Determine tenant status based on registration status
                $tenantStatus = $registration->status === 'approved' ? 'active' : 'pending';

                // Create tenant
                $tenant = Tenant::create([
                    'id' => $registration->subdomain,
                    'name' => $registration->clinic_name,
                    'owner_name' => $registration->first_name . ' ' . $registration->last_name,
                    'email' => $registration->email,
                    'phone' => $registration->phone,
                    'street' => $registration->street,
                    'barangay' => $registration->barangay,
                    'city' => $registration->city,
                    'province' => $registration->province,
                    'status' => $tenantStatus,
                ]);

                // Create domain
                $domain = $tenant->domains()->create([
                    'domain' => $registration->subdomain,
                ]);

                // Update tenant with domain_id
                $tenant->update(['domain_id' => $domain->id]);

                // Initialize tenancy to create user in tenant database
                try {
                    tenancy()->initialize($tenant);

                    // Create admin user
                    $user = \App\Models\User::create([
                        'name' => $registration->first_name . ' ' . $registration->last_name,
                        'email' => $registration->email,
                        'password' => decrypt($registration->password),
                        'requires_password_change' => true,
                    ]);

                    // Assign Owner role
                    $user->assignRole('Owner');

                    tenancy()->end();
                }
                catch (\Exception $e) {
                    $this->warn("Could not create user in tenant DB for {$registration->clinic_name}: " . $e->getMessage());
                }

                // Create subscription if payment was made
                if ($registration->amount_paid > 0) {
                    $tenant->subscriptions()->create([
                        'subscription_plan_id' => $registration->subscription_plan_id,
                        'stripe_status' => 'active',
                        'stripe_price' => $registration->amount_paid,
                        'billing_cycle' => $registration->billing_cycle,
                        'payment_method' => 'card',
                        'payment_status' => 'paid',
                    ]);
                }

                DB::commit();

                $this->info("Migrated: {$registration->clinic_name} ({$registration->subdomain}) - Status: {$tenantStatus}");
                $migrated++;

            }
            catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to migrate {$registration->clinic_name}: " . $e->getMessage());
            }
        }

        $this->info("\nMigration complete!");
        $this->info("Migrated: {$migrated}");
        $this->info("Skipped: {$skipped}");

        return 0;
    }
}
