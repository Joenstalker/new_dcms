<?php

namespace App\Console\Commands;

use App\Mail\RegistrationRefunded;
use App\Mail\RegistrationPending;
use App\Models\PendingRegistration;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;
use App\Mail\TenantApproved;

class ProcessExpiredRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registrations:process-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expired pending registrations (auto-approve or refund)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing expired registrations...');

        // First, process auto-approvals
        $this->processAutoApprovals();

        // Then, process remaining expired registrations for refund
        $this->processRefunds();

        $this->info('Done processing expired registrations.');
        return 0;
    }

    /**
     * Process auto-approvals for registrations where auto-approve is enabled.
     */
    protected function processAutoApprovals(): void
    {
        // Check if global auto-approve is enabled
        $globalAutoApprove = SystemSetting::get('pending_auto_approve_enabled', false);

        if (!$globalAutoApprove) {
            $this->info('Auto-approve is disabled globally. Skipping auto-approval.');
            return;
        }

        // Get registrations that are expired and eligible for auto-approve
        $eligibleRegistrations = PendingRegistration::where('status', PendingRegistration::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->where(function ($query) {
                $query->where('auto_approve_enabled', true)
                    ->orWhereNull('auto_approve_enabled');
            })
            ->get();

        $this->info("Found {$eligibleRegistrations->count()} registrations eligible for auto-approve.");

        foreach ($eligibleRegistrations as $registration) {
            $this->autoApproveRegistration($registration);
        }
    }

    /**
     * Auto-approve a registration.
     */
    protected function autoApproveRegistration(PendingRegistration $registration): void
    {
        $this->info("Auto-approving registration for: {$registration->clinic_name}");

        try {
            DB::beginTransaction();

            // Check if tenant already exists (created during payment)
            $tenant = Tenant::where('id', $registration->subdomain)->first();

            if ($tenant) {
                // Tenant exists - initialize tenancy and create user
                tenancy()->initialize($tenant);

                // Create admin user in tenant database
                $user = \App\Models\User::create([
                    'name' => $registration->first_name . ' ' . $registration->last_name,
                    'email' => $registration->email,
                    'password' => Hash::make($registration->password),
                ]);

                // Assign Owner role
                $user->assignRole('Owner');

                // End tenancy
                tenancy()->end();

                // Update tenant status to active and ensure landing defaults
                $tenant->update([
                    'status' => 'active',
                    'landing_page_config' => Tenant::mergeLandingPageConfig(
                        is_array($tenant->landing_page_config) ? $tenant->landing_page_config : null
                    ),
                ]);

                // Create subscription if not exists
                if (!$tenant->subscriptions()->exists()) {
                    $subscription = $tenant->subscriptions()->create([
                        'subscription_plan_id' => $registration->subscription_plan_id,
                        'stripe_status' => 'active',
                        'stripe_price' => $registration->amount_paid,
                        'billing_cycle' => $registration->billing_cycle,
                        'payment_method' => 'card',
                        'payment_status' => 'paid',
                    ]);
                }
            }
            else {
                // Tenant doesn't exist - create from scratch (legacy/edge case)
                $tenantId = $registration->subdomain;

                $tenant = Tenant::create([
                    'id' => $tenantId,
                    'name' => $registration->clinic_name,
                    'owner_name' => $registration->first_name . ' ' . $registration->last_name,
                    'status' => 'active',
                    'email' => $registration->email,
                    'phone' => $registration->phone,
                    'street' => $registration->street,
                    'barangay' => $registration->barangay,
                    'city' => $registration->city,
                    'province' => $registration->province,
                ]);

                // Create domain
                $domain = $tenant->domains()->create([
                    'domain' => $registration->subdomain,
                ]);

                // Update tenant with domain_id
                $tenant->update(['domain_id' => $domain->id]);

                // Initialize tenancy to create database and user
                tenancy()->initialize($tenant);

                // Create admin user in tenant database
                $user = \App\Models\User::create([
                    'name' => $registration->first_name . ' ' . $registration->last_name,
                    'email' => $registration->email,
                    'password' => Hash::make($registration->password),
                ]);

                // Assign Owner role
                $user->assignRole('Owner');

                // End tenancy
                tenancy()->end();

                // Create subscription
                $subscription = $tenant->subscriptions()->create([
                    'subscription_plan_id' => $registration->subscription_plan_id,
                    'stripe_status' => 'active',
                    'stripe_price' => $registration->amount_paid,
                    'billing_cycle' => $registration->billing_cycle,
                    'payment_method' => 'card',
                    'payment_status' => 'paid',
                ]);
            }

            // Update pending registration status
            $registration->update([
                'status' => PendingRegistration::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

            DB::commit();

            // Send approval email
            try {
                $tenantUrl = Tenant::publicWebsiteUrlForSubdomain($registration->subdomain);
                Mail::to($registration->email)->send(new TenantApproved($registration, $tenantUrl));
                $this->info("Approval email sent to: {$registration->email}");
            }
            catch (\Exception $e) {
                Log::warning('Failed to send approval email: ' . $e->getMessage());
            }

            $this->info("Registration auto-approved: {$registration->subdomain}");
        }
        catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to auto-approve registration: ' . $e->getMessage());
            $this->error("Failed to auto-approve: " . $e->getMessage());
        }
    }

    /**
     * Process refunds for remaining expired registrations.
     */
    protected function processRefunds(): void
    {
        $now = now('UTC');
        
        // Get expired pending registrations that weren't auto-approved
        // We only process them if they are TRULY expired based on their own expires_at
        $expiredRegistrations = PendingRegistration::where('status', PendingRegistration::STATUS_PENDING)
            ->where('expires_at', '<', $now)
            ->get();

        $this->info("Current Time (UTC): " . $now);
        $this->info("Found {$expiredRegistrations->count()} registrations past their expiry date.");

        foreach ($expiredRegistrations as $registration) {
            $this->info("Checking ID: {$registration->id}, Subdomain: {$registration->subdomain}");

            // Check if auto-refund is enabled for this registration
            if (!$registration->isAutoRefundEnabled()) {
                $this->info("Skipping refund: Auto-refund is disabled for {$registration->subdomain}");
                continue;
            }

            // Safety Check: If a tenant already exists for this subdomain, DO NOT refund.
            if (Tenant::where('id', $registration->subdomain)->exists()) {
                $this->info("Skipping refund: Tenant already exists for {$registration->subdomain}");
                
                $registration->update([
                    'status' => PendingRegistration::STATUS_APPROVED,
                    'approved_at' => $registration->approved_at ?? now(),
                ]);
                continue;
            }

            $this->info("Processing refund for ID: {$registration->id}, Subdomain: {$registration->subdomain}, Expired At: {$registration->expires_at}");
            $this->processRegistration($registration);
        }
    }

    /**
     * Process a single expired registration (refund).
     */
    protected function processRegistration(PendingRegistration $registration): void
    {
        $this->info("Processing registration for: {$registration->clinic_name}");

        $refundSuccessful = false;

        // Process refund via Stripe if payment exists
        if ($registration->stripe_payment_intent_id) {
            $refundSuccessful = $this->processRefund($registration);
        } else {
            // If no payment intent, we just mark it as refunded (or abandoned)
            $registration->update([
                'status' => PendingRegistration::STATUS_REFUNDED,
            ]);
            $refundSuccessful = true;
        }

        // Only send refund email if the refund was actually processed (or no payment was made)
        if ($refundSuccessful) {
            try {
                Mail::to($registration->email)->send(
                    new RegistrationRefunded($registration, $registration->amount_paid ?? 0)
                );
                $this->info("Refund email sent to: {$registration->email}");
            }
            catch (\Exception $e) {
                $this->error("Failed to send refund email: " . $e->getMessage());
            }

            $this->info("Registration marked as refunded: {$registration->subdomain}");
        } else {
            $this->error("Skipping refund email because refund failed for: {$registration->subdomain}");
        }
    }

    /**
     * Process refund via Stripe.
     */
    protected function processRefund(PendingRegistration $registration): bool
    {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

            $refund = $stripe->refunds->create([
                'payment_intent' => $registration->stripe_payment_intent_id,
            ]);

            // Update registration status
            $registration->update([
                'status' => PendingRegistration::STATUS_REFUNDED,
            ]);

            $this->info("Refund processed successfully for: {$registration->subdomain}");
            return true;
        }
        catch (\Exception $e) {
            $this->error("Failed to process refund: " . $e->getMessage());
            return false;
        }
    }
}
