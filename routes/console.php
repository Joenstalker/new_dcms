<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('fix:tenant-domains', function () {
    $tenants = \App\Models\Tenant::all();
    $count = 0;

    foreach ($tenants as $tenant) {
        if ($tenant->domains()->count() === 0) {
            $subdomain = $tenant->subdomain;
            $this->info("Creating domain '{$subdomain}' for tenant '{$tenant->id}'");

            $tenant->domains()->create([
                'domain' => $subdomain,
            ]);

            $count++;
        }
    }

    $this->info("Complete! Fixed {$count} tenants.");
})->purpose('Add missing domain records for tenants that were created but failed halfway');

// Pending Registration Automated Tasks
Schedule::command('registrations:process-expired')->everyMinute();
Schedule::command('registrations:send-reminders')->hourly();

// Appointment reminders — run daily at 8 AM
Schedule::command('appointments:send-reminders')->dailyAt('08:00');

// Passive system updates checker
Schedule::command('system:check-updates')->hourly();
