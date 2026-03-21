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
        $autoApproveHours = SystemSetting::get('pending_auto_approve_hours', 168);

        $eligibleRegistrations = PendingRegistration::where('status', PendingRegistration::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->where(function ($query) use ($autoApproveHours) {
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

        // Check if subdomain already exists
        if (Domain::where('domain', $registration->subdomain)->exists()) {
            $this->warn("Subdomain already exists. Marking as refunded: {$registration->subdomain}");
            $this->processRegistration($registration);
            return;
        }

        try {
            DB::beginTransaction();

            // Create tenant
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

            // Create subscription
            $subscription = $tenant->subscriptions()->create([
                'subscription_plan_id' => $registration->subscription_plan_id,
                'stripe_status' => 'active',
                'stripe_price' => $registration->amount_paid,
                'billing_cycle' => $registration->billing_cycle,
                'payment_method' => 'card',
                'payment_status' => 'paid',
            ]);

            // End tenancy
            tenancy()->end();

            // Update pending registration status
            $registration->update([
                'status' => PendingRegistration::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

            DB::commit();

            // Send approval email
            try {
                $appUrl = config('app.url');
                $parsed = parse_url($appUrl);
                $host = $parsed['host'] ?? str_replace(['http://', 'https://'], '', $appUrl);
                $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
                $tenantUrl = 'http://' . $registration->subdomain . '.' . $host . $port;
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
        // Get expired pending registrations that weren't auto-approved
        $expiredRegistrations = PendingRegistration::expired()
            ->where('status', PendingRegistration::STATUS_PENDING)
            ->get();

        $this->info("Found {$expiredRegistrations->count()} expired registrations to process for refund.");

        foreach ($expiredRegistrations as $registration) {
            $this->processRegistration($registration);
        }
    }

    /**
     * Process a single expired registration (refund).
     */
    protected function processRegistration(PendingRegistration $registration): void
    {
        $this->info("Processing registration for: {$registration->clinic_name}");

        // Process refund via Stripe if payment exists
        if ($registration->stripe_payment_intent_id) {
            $this->processRefund($registration);
        }

        // Send refund email
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
