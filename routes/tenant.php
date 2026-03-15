<?php

declare(strict_types = 1)
;

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/* |-------------------------------------------------------------------------- | Tenant Routes |-------------------------------------------------------------------------- | | Here you can register the tenant routes for your application. | These routes are loaded by the TenantRouteServiceProvider. | | Feel free to customize them however you want. Good luck! | */

Route::middleware([
    'web',
    InitializeTenancyByDomain::class ,
    PreventAccessFromCentralDomains::class ,
    \App\Http\Middleware\CheckTenantStatus::class ,
])->group(function () {
    Route::get('/', function () {
            return redirect()->route('login');
        }
        );

        // Public QR Booking Route (no auth required — patients scan QR code)
        Route::get('/book', [\App\Http\Controllers\BookingController::class , 'create'])->name('tenant.book.create');
        Route::post('/book', [\App\Http\Controllers\BookingController::class , 'store'])->name('tenant.book.store');

        require __DIR__ . '/auth.php';

        // Authenticated Tenant Routes
        // check.subscription (no feature arg) ensures an active subscription exists
        // and shares plan info with Inertia on every authenticated request.
        Route::middleware(['auth', 'check.subscription'])->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class , 'index'])->name('tenant.dashboard');

            // Patient management — enforces max_patients limit on create
            Route::get('patients', [\App\Http\Controllers\PatientController::class , 'index'])->name('patients.index');
            Route::get('patients/create', [\App\Http\Controllers\PatientController::class , 'create'])->name('patients.create');
            Route::post('patients', [\App\Http\Controllers\PatientController::class , 'store'])
                ->middleware('check.subscription:max_patients')
                ->name('patients.store');
            Route::get('patients/{patient}', [\App\Http\Controllers\PatientController::class , 'show'])->name('patients.show');
            Route::put('patients/{patient}', [\App\Http\Controllers\PatientController::class , 'update'])->name('patients.update');
            Route::delete('patients/{patient}', [\App\Http\Controllers\PatientController::class , 'destroy'])->name('patients.destroy');

            // Appointment management — enforces max_appointments limit on create
            Route::get('appointments', [\App\Http\Controllers\AppointmentController::class , 'index'])->name('appointments.index');
            Route::post('appointments', [\App\Http\Controllers\AppointmentController::class , 'store'])
                ->middleware('check.subscription:max_appointments')
                ->name('appointments.store');
            Route::put('appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class , 'update'])->name('appointments.update');

            // Treatment records
            Route::get('treatments', [\App\Http\Controllers\TreatmentController::class , 'index'])->name('treatments.index');
            Route::post('treatments', [\App\Http\Controllers\TreatmentController::class , 'store'])->name('treatments.store');

            // Billing
            Route::get('billing', [\App\Http\Controllers\BillingController::class , 'index'])->name('billing.index');
            Route::post('billing', [\App\Http\Controllers\BillingController::class , 'store'])->name('billing.store');
            Route::put('billing/{invoice}', [\App\Http\Controllers\BillingController::class , 'update'])->name('billing.update');

            // Owner only routes
            Route::middleware(['role:Owner'])->group(function () {
                    // Staff management — enforces max_users limit on create
                    Route::get('staff/schedules', [\App\Http\Controllers\StaffController::class , 'schedules'])->name('staff.schedules');
                    Route::get('staff', [\App\Http\Controllers\StaffController::class , 'index'])->name('staff.index');
                    Route::get('staff/create', [\App\Http\Controllers\StaffController::class , 'create'])->name('staff.create');
                    Route::post('staff', [\App\Http\Controllers\StaffController::class , 'store'])
                        ->middleware('check.subscription:max_users')
                        ->name('staff.store');
                    Route::get('staff/{staff}/edit', [\App\Http\Controllers\StaffController::class , 'edit'])->name('staff.edit');
                    Route::put('staff/{staff}', [\App\Http\Controllers\StaffController::class , 'update'])->name('staff.update');
                    Route::delete('staff/{staff}', [\App\Http\Controllers\StaffController::class , 'destroy'])->name('staff.destroy');
                    Route::put('staff/{staff}/permissions', [\App\Http\Controllers\StaffController::class , 'updatePermissions'])->name('staff.update-permissions');

                    // Reports
                    Route::get('reports', [\App\Http\Controllers\ReportController::class , 'index'])->name('reports.index');

                    // Settings
                    Route::get('settings', [\App\Http\Controllers\SettingsController::class , 'index'])->name('settings.index');
                    Route::post('settings', [\App\Http\Controllers\SettingsController::class , 'update'])->name('settings.update');

                    // Stripe Customer Portal — self-service billing (upgrade, downgrade, cancel, update card)
                    Route::get('billing-portal', [\App\Http\Controllers\BillingPortalController::class , 'redirect'])->name('billing.portal');
                }
                );

                // Services — accessible by Owner, Dentist, and Assistant
                Route::middleware(['role:Owner|Dentist|Assistant'])->group(function () {
                    Route::resource('services', \App\Http\Controllers\ServiceController::class);
                }
                );

                // Service approval — Owner and Dentist only
                Route::middleware(['role:Owner|Dentist'])->group(function () {
                    Route::post('services/{service}/approve', [\App\Http\Controllers\ServiceController::class , 'approve'])->name('services.approve');
                    Route::post('services/{service}/reject', [\App\Http\Controllers\ServiceController::class , 'reject'])->name('services.reject');
                }
                );

                // Notifications
                Route::get('notifications', [\App\Http\Controllers\Tenant\NotificationController::class , 'index'])->name('notifications.index');
                Route::get('notifications/recent', [\App\Http\Controllers\Tenant\NotificationController::class , 'getRecent'])->name('notifications.recent');
                Route::get('notifications/count', [\App\Http\Controllers\Tenant\NotificationController::class , 'getUnreadCount'])->name('notifications.count');
                Route::put('notifications/{id}/read', [\App\Http\Controllers\Tenant\NotificationController::class , 'markAsRead'])->name('notifications.mark-read');
                Route::put('notifications/read-all', [\App\Http\Controllers\Tenant\NotificationController::class , 'markAllAsRead'])->name('notifications.mark-all-read');
                Route::delete('notifications/{id}', [\App\Http\Controllers\Tenant\NotificationController::class , 'destroy'])->name('notifications.destroy');
                Route::get('notifications/settings', [\App\Http\Controllers\Tenant\NotificationController::class , 'settings'])->name('notifications.settings');
                Route::put('notifications/settings', [\App\Http\Controllers\Tenant\NotificationController::class , 'updateSettings'])->name('notifications.settings.update');
                Route::post('notifications/send-message', [\App\Http\Controllers\Tenant\NotificationController::class , 'sendMessage'])->name('notifications.send-message');
            }
            );
        });
