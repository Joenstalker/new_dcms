<?php

namespace App\Console\Commands;

use App\Mail\RegistrationPending;
use App\Models\PendingRegistration;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendPendingRegistrationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registrations:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to pending registrations approaching expiry';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if reminders are enabled globally
        $globalEnabled = SystemSetting::get('pending_reminder_global_enabled', true);

        if (!$globalEnabled) {
            $this->info('Reminders are disabled globally. Exiting.');
            return 0;
        }

        $this->info('Sending pending registration reminders...');

        // Get registrations needing reminders
        $registrations = PendingRegistration::needingReminders()->get();

        $this->info("Found {$registrations->count()} registrations needing reminders.");

        $sent = 0;
        $failed = 0;

        foreach ($registrations as $registration) {
            if ($this->sendReminder($registration)) {
                $sent++;
            }
            else {
                $failed++;
            }
        }

        $this->info("Reminders sent: {$sent}, Failed: {$failed}");

        return 0;
    }

    /**
     * Send reminder email to a registration.
     */
    protected function sendReminder(PendingRegistration $registration): bool
    {
        // Check if reminder is enabled for this specific registration
        if (!$registration->isReminderEnabled()) {
            $this->info("Reminder disabled for registration: {$registration->clinic_name}");
            return false;
        }

        try {
            // Send reminder email
            Mail::to($registration->email)->send(new RegistrationPending($registration));

            // Update reminder_sent_at
            $registration->update([
                'reminder_sent_at' => now(),
            ]);

            $this->info("Reminder sent to: {$registration->email}");
            return true;
        }
        catch (\Exception $e) {
            Log::error("Failed to send reminder to {$registration->email}: " . $e->getMessage());
            $this->error("Failed to send reminder to: {$registration->email}");
            return false;
        }
    }
}
