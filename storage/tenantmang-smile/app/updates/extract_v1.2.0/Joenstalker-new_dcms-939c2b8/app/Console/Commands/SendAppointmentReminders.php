<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Tenant;
use App\Services\NotificationTriggerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Send email reminders for appointments happening tomorrow (runs across all tenants)';

    public function handle(): int
    {
        $tenants = Tenant::where('status', 'active')->get();
        $totalSent = 0;

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use (&$totalSent) {
                    $service = app(NotificationTriggerService::class);

                    if (!$service->isEnabled()) return;

                    $tomorrow = now()->addDay()->startOfDay();
                    $tomorrowEnd = now()->addDay()->endOfDay();

                    $appointments = Appointment::with(['patient', 'dentist'])
                        ->whereBetween('appointment_date', [$tomorrow, $tomorrowEnd])
                        ->where('status', 'scheduled')
                        ->get();

                    foreach ($appointments as $appointment) {
                        $service->onAppointmentReminder($appointment);
                        $totalSent++;
                    }
                });
            } catch (\Exception $e) {
                Log::error("Failed to send reminders for tenant {$tenant->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$totalSent} appointment reminders across {$tenants->count()} tenants.");

        return Command::SUCCESS;
    }
}
