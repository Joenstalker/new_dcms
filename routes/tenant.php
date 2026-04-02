<?php

declare(strict_types = 1)
;

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/* |-------------------------------------------------------------------------- | Tenant Routes |-------------------------------------------------------------------------- | | Here you can register the tenant routes for your application. | These routes are loaded by the TenantRouteServiceProvider. | | Feel free to customize them however you want. Good luck! | */

Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class ,
    \App\Http\Middleware\SetTenantUrl::class ,
    PreventAccessFromCentralDomains::class ,
    \App\Http\Middleware\CheckTenantStatus::class ,
])->group(function () {
    // Tenant storage — serves files from the tenant's isolated storage directory.
    // No auth required (profile pictures must load on login/landing pages).
    // Tenant isolation is guaranteed by InitializeTenancyBySubdomain.
    Route::get('/tenant-storage/{path}', [\App\Http\Controllers\Tenant\TenantStorageController::class , 'serve'])
        ->where('path', '.*')
        ->name('tenant.storage');

    // Serve branding logos from tenant database (no auth — images needed on public pages)
    Route::get('settings/branding/logo/{key}', [\App\Http\Controllers\Tenant\SettingsController::class , 'serveLogo'])
        ->name('settings.logo');

    Route::get('/', [\App\Http\Controllers\Tenant\LandingController::class , 'index'])->name('tenant.landing');
    Route::post('/concerns', [\App\Http\Controllers\Tenant\LandingController::class , 'submitConcern'])->name('tenant.concern.store');
    Route::post('/contact-support', [\App\Http\Controllers\ContactController::class , 'submit'])->name('tenant.contact.submit');
    Route::patch('/concerns/{concern}', [\App\Http\Controllers\Tenant\ConcernController::class , 'update'])->name('tenant.concern.update');

    // Public QR Booking Route (no auth required — patients scan QR code)
    Route::get('/book', [\App\Http\Controllers\BookingController::class , 'create'])->name('tenant.book.create');
    Route::post('/book', [\App\Http\Controllers\BookingController::class , 'store'])
        ->middleware('check.subscription:max_appointments')
        ->name('tenant.book.store');

    // Tenant Auth API Routes (Modal-based login)
    Route::middleware('guest')->group(function () {
            Route::post('/api/login', [\App\Http\Controllers\Tenant\Auth\TenantAuthController::class , 'store'])->name('tenant.login.store');
            Route::post('/api/login/google', [\App\Http\Controllers\Auth\GoogleAuthController::class , 'handleGoogleLogin'])->name('tenant.login.google');

            // Code-based password reset
            Route::post('/api/password/code/send', [\App\Http\Controllers\Tenant\Auth\TenantAuthController::class , 'sendResetCode'])->name('tenant.password.send-code');
            Route::post('/api/password/code/verify', [\App\Http\Controllers\Tenant\Auth\TenantAuthController::class , 'verifyResetCode'])->name('tenant.password.verify-code');
            Route::post('/api/password/code/reset', [\App\Http\Controllers\Tenant\Auth\TenantAuthController::class , 'resetWithCode'])->name('tenant.password.reset-with-code');

            // Legacy/Traditional reset routes (keeping for compatibility if needed elsewhere)
            Route::post('/api/password/email', [\App\Http\Controllers\Tenant\Auth\TenantAuthController::class , 'sendResetLink'])->name('tenant.password.email');
            Route::post('/api/password/reset', [\App\Http\Controllers\Tenant\Auth\TenantAuthController::class , 'resetPassword'])->name('tenant.password.store');
            Route::get('/reset-password/{token}', [\App\Http\Controllers\Tenant\Auth\ResetPasswordPageController::class , 'show'])->name('tenant.password.reset');
        }
        );

        require __DIR__ . '/auth.php';

        // Authenticated Tenant Routes
        // check.subscription (no feature arg) ensures an active subscription exists
        // and shares plan info with Inertia on every authenticated request.
        Route::middleware(['auth', 'check.subscription'])->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class , 'index'])->name('tenant.dashboard');

            // Profile management (all authenticated tenant users)
            Route::get('profile', [\App\Http\Controllers\ProfileController::class , 'edit'])->name('tenant.profile.edit');
            Route::patch('profile', [\App\Http\Controllers\ProfileController::class , 'update'])->name('tenant.profile.update');
            Route::post('profile/picture', [\App\Http\Controllers\ProfileController::class , 'updatePicture'])->name('tenant.profile.update-picture');
            Route::delete('profile', [\App\Http\Controllers\ProfileController::class , 'destroy'])->name('tenant.profile.destroy');

            // Password update (tenant-scoped)
            Route::put('password', [\App\Http\Controllers\Auth\PasswordController::class , 'update'])->name('tenant.password.update');

            // Staff Settings (personal, permission-gated per section — NOT inside Owner block)
            Route::get('my-settings', [\App\Http\Controllers\Tenant\StaffSettingsController::class , 'index'])->name('staff-settings.index');
            Route::put('my-settings/calendar-color', [\App\Http\Controllers\Tenant\StaffSettingsController::class , 'updateCalendarColor'])->name('staff-settings.calendar-color');
            Route::put('my-settings/notifications', [\App\Http\Controllers\Tenant\StaffSettingsController::class , 'updateNotificationPreferences'])->name('staff-settings.notifications');
            Route::put('my-settings/working-hours', [\App\Http\Controllers\Tenant\StaffSettingsController::class , 'updateWorkingHours'])->name('staff-settings.working-hours');

            // Patient management — enforces max_patients limit on create
            Route::middleware(['permission:view patients'])->group(function () {
                    Route::get('patients', [\App\Http\Controllers\PatientController::class , 'index'])->name('patients.index');
                    Route::get('patients/create', [\App\Http\Controllers\PatientController::class , 'create'])->name('patients.create');
                    Route::post('patients', [\App\Http\Controllers\PatientController::class , 'store'])
                        ->middleware('check.subscription:max_patients')
                        ->name('patients.store');
                    Route::get('patients/{patient}', [\App\Http\Controllers\PatientController::class , 'show'])->name('patients.show');
                    Route::get('patients/{patient}/pdf', [\App\Http\Controllers\PatientController::class , 'downloadPdf'])->name('patients.pdf');
                    Route::get('patients/{patient}/edit', [\App\Http\Controllers\PatientController::class , 'edit'])->name('patients.edit');
                    Route::get('patients/{patient}/delete', [\App\Http\Controllers\PatientController::class , 'delete'])->name('patients.delete');
                    Route::put('patients/{patient}', [\App\Http\Controllers\PatientController::class , 'update'])->name('patients.update');
                    Route::delete('patients/{patient}', [\App\Http\Controllers\PatientController::class , 'destroy'])->name('patients.destroy');
                }
                );

                // Appointment management — enforces max_appointments limit on create
                // Appointment management
                Route::get('appointments', [\App\Http\Controllers\AppointmentController::class , 'index'])
                    ->middleware('permission:view appointments')
                    ->name('appointments.index');

                Route::post('appointments', [\App\Http\Controllers\AppointmentController::class , 'store'])
                    ->middleware(['permission:create appointments', 'check.subscription:max_appointments'])
                    ->name('appointments.store');

                Route::put('appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class , 'update'])
                    ->middleware('permission:edit appointments')
                    ->name('appointments.update');

                Route::post('appointments/{appointment}/approve', [\App\Http\Controllers\AppointmentController::class , 'approve'])
                    ->middleware('permission:edit appointments')
                    ->name('appointments.approve');

                Route::delete('appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class , 'destroy'])
                    ->middleware('permission:delete appointments')
                    ->name('appointments.destroy');

                // Treatment records
                Route::middleware(['permission:view treatments'])->group(function () {
                    Route::get('treatments', [\App\Http\Controllers\TreatmentController::class , 'index'])->name('treatments.index');
                    Route::post('treatments', [\App\Http\Controllers\TreatmentController::class , 'store'])->middleware('permission:create treatments')->name('treatments.store');
                    Route::get('treatments/{treatment}', [\App\Http\Controllers\TreatmentController::class , 'show'])->name('treatments.show');
                    Route::put('treatments/{treatment}', [\App\Http\Controllers\TreatmentController::class , 'update'])->middleware('permission:edit treatments')->name('treatments.update');
                    Route::delete('treatments/{treatment}', [\App\Http\Controllers\TreatmentController::class , 'destroy'])->middleware('permission:delete treatments')->name('treatments.destroy');
                }
                );

                // Billing
                Route::middleware(['permission:view billing'])->group(function () {
                    Route::get('billing', [\App\Http\Controllers\BillingController::class , 'index'])->name('billing.index');
                    Route::post('billing', [\App\Http\Controllers\BillingController::class , 'store'])->name('billing.store');
                    Route::put('billing/{invoice}', [\App\Http\Controllers\BillingController::class , 'update'])->name('billing.update');
                }
                );

                // Owner only routes
                Route::middleware(['role:Owner'])->group(function () {
                    // Staff management — enforces max_users limit on create
                    Route::get('staff', [\App\Http\Controllers\StaffController::class , 'index'])->name('staff.index');
                    Route::post('staff', [\App\Http\Controllers\StaffController::class , 'store'])
                        ->middleware('check.subscription:max_users')
                        ->name('staff.store');
                    Route::put('staff/{staff}', [\App\Http\Controllers\StaffController::class , 'update'])->name('staff.update');
                    Route::delete('staff/{staff}', [\App\Http\Controllers\StaffController::class , 'destroy'])->name('staff.destroy');
                    Route::post('staff/bulk-permissions', [\App\Http\Controllers\StaffController::class , 'bulkUpdatePermissions'])->name('staff.bulk-update-permissions');

                    // Reports
                    Route::get('reports', [\App\Http\Controllers\ReportController::class , 'index'])->name('reports.index');
                    Route::get('reports/export/{format}', [\App\Http\Controllers\ReportController::class , 'export'])->name('reports.export');

                    // Analytics (Ultimate only)
                    Route::get('analytics', [\App\Http\Controllers\Tenant\AnalyticsController::class , 'index'])
                        ->name('analytics.index')
                        ->middleware('check.subscription:advanced_analytics');

                    // Activity Logs
                    Route::get('activity-logs', [\App\Http\Controllers\ActivityLogController::class , 'index'])
                        ->name('activity-logs.index');

                    // Branches (Ultimate only)
                    Route::middleware(['check.subscription:multi_branch'])->group(function () {
                            Route::get('branches', [\App\Http\Controllers\Tenant\BranchController::class , 'index'])->name('branches.index');
                            Route::post('branches', [\App\Http\Controllers\Tenant\BranchController::class , 'store'])->name('branches.store');
                            Route::put('branches/{branch}', [\App\Http\Controllers\Tenant\BranchController::class , 'update'])->name('branches.update');
                            Route::delete('branches/{branch}', [\App\Http\Controllers\Tenant\BranchController::class , 'destroy'])->name('branches.destroy');
                            Route::post('branches/switch', [\App\Http\Controllers\Tenant\BranchController::class , 'switchBranch'])->name('branches.switch');
                        }
                        );

                        // Settings
                        Route::get('settings', [\App\Http\Controllers\Tenant\SettingsController::class , 'index'])->name('settings.index');
                        // Settings - Features
                        Route::get('settings/features', [\App\Http\Controllers\Tenant\SettingsController::class , 'features'])
                            ->name('settings.features')
                            ->middleware('check.subscription:custom_system_features');

                        // Settings - Updates (OTA)
                        Route::get('settings/updates', [\App\Http\Controllers\Tenant\SettingsController::class , 'updates'])->name('settings.updates');
                        Route::post('settings/updates/apply', [\App\Http\Controllers\Tenant\SettingsController::class , 'applyUpdates'])->name('settings.updates.apply');
                        Route::get('settings/updates/check', [\App\Http\Controllers\Tenant\SettingsController::class , 'checkUpdates'])->name('settings.updates.check');

                        // System Update Core API endpoints
                        Route::get('api/system/update-status', [\App\Http\Controllers\Tenant\SystemUpdateController::class , 'getStatus'])->name('api.system.update-status');
                        Route::post('api/system/update', [\App\Http\Controllers\Tenant\SystemUpdateController::class , 'update'])->name('api.system.update');

                        // Stripe Customer Portal — self-service billing (upgrade, downgrade, cancel, update card)
                        Route::get('billing-portal', [\App\Http\Controllers\BillingPortalController::class , 'redirect'])->name('billing.portal');
                    }
                    );

                    // Custom Branding — Owner OR staff with 'manage clinic branding' permission
                    Route::middleware(['role:Owner|Assistant', 'check.subscription:custom_branding'])->group(function () {
                    Route::get('settings/branding', [\App\Http\Controllers\Tenant\SettingsController::class , 'branding'])
                        ->name('settings.branding');
                    Route::post('settings', [\App\Http\Controllers\Tenant\SettingsController::class , 'update'])
                        ->name('settings.update');
                    Route::post('settings/logo', [\App\Http\Controllers\Tenant\SettingsController::class , 'uploadLogo'])
                        ->name('settings.logo.upload');
                    Route::delete('settings/logo', [\App\Http\Controllers\Tenant\SettingsController::class , 'deleteLogo'])
                        ->name('settings.logo.delete');
                }
                );

                // Services — accessible by Owner, Dentist, and Assistant
                Route::middleware(['role:Owner|Dentist|Assistant', 'permission:view services'])->group(function () {
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
                Route::middleware(['check.subscription:sms_notifications'])->group(function () {
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
            }
            );
        });
