<?php

namespace App\Console\Commands;

use App\Mail\RegistrationRefunded;
use App\Models\PendingRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

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
    protected $description = 'Process expired pending registrations and issue refunds';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing expired registrations...');

        // Get expired pending registrations
        $expiredRegistrations = PendingRegistration::expired()
            ->where('status', PendingRegistration::STATUS_PENDING)
            ->get();

        $this->info("Found {$expiredRegistrations->count()} expired registrations.");

        foreach ($expiredRegistrations as $registration) {
            $this->processRegistration($registration);
        }

        $this->info('Done processing expired registrations.');
        return 0;
    }

    /**
     * Process a single expired registration.
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
