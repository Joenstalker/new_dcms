<?php

namespace App\Console\Commands;

use App\Mail\TenantApproved;
use App\Models\PendingRegistration;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ResendApprovedEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registrations:resend-approved {subdomain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resend the approval email to a tenant by their subdomain';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subdomain = $this->argument('subdomain');
        $this->info("Searching for registration with subdomain: {$subdomain}");

        $registration = PendingRegistration::where('subdomain', $subdomain)->first();

        if (!$registration) {
            $this->error("No registration found for subdomain: {$subdomain}");
            return 1;
        }

        if ($registration->status !== PendingRegistration::STATUS_APPROVED) {
            $this->warn("Note: This registration is currently in status: {$registration->status}");
            if (!$this->confirm('Do you want to send the approval email anyway?', false)) {
                return 0;
            }
        }

        try {
            $tenantUrl = Tenant::publicWebsiteUrlForSubdomain($subdomain);
            Mail::to($registration->email)->send(new TenantApproved($registration, $tenantUrl));
            
            $this->info("Success! Approval email resent to: {$registration->email}");
            $this->info("Clinic: {$registration->clinic_name}");
            $this->info("URL: {$tenantUrl}");
        } catch (\Exception $e) {
            $this->error("Failed to resend email: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
