<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BillingPortalController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\Tenant\AnalyticsController;
use App\Http\Controllers\Tenant\Auth\ResetPasswordPageController;
use App\Http\Controllers\Tenant\Auth\TenantAuthController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\ConcernController;
use App\Http\Controllers\Tenant\LandingController;
use App\Http\Controllers\Tenant\MedicalRecordController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\Tenant\PaymentHistoryController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\StaffSettingsController;
use App\Http\Controllers\Tenant\Support\SupportController;
use App\Http\Controllers\Tenant\SystemUpdateController;
use App\Http\Controllers\Tenant\TenantStorageController;
use App\Http\Controllers\TreatmentController;
use App\Http\Middleware\CheckTenantStatus;
use App\Http\Middleware\SetTenantUrl;
use Illuminate\Support\Facades\Route;

/* |-------------------------------------------------------------------------- | Tenant Routes |-------------------------------------------------------------------------- | | Here you can register the tenant routes for your application. | These routes are loaded by the TenantRouteServiceProvider. | | Feel free to customize them however you want. Good luck! | */

Route::middleware([
    'web',
    'tenant.init.preview_or_subdomain',
    SetTenantUrl::class,
    'tenant.prevent.central_or_preview',
    CheckTenantStatus::class,
])->group(function () {
    // Tenant storage — serves files from the tenant's isolated storage directory.
    // No auth required (profile pictures must load on login/landing pages).
    // Tenant isolation is guaranteed by InitializeTenancyBySubdomain.
    Route::get('/tenant-storage/{path}', [TenantStorageController::class, 'serve'])
        ->where('path', '.*')
        ->name('tenant.storage');

    // Serve branding logos from tenant database (no auth — images needed on public pages)
    Route::get('settings/branding/logo/{key}', [SettingsController::class, 'serveLogo'])
        ->name('settings.logo');

    Route::get('/', [LandingController::class, 'index'])->name('tenant.landing');
    Route::post('/concerns', [LandingController::class, 'submitConcern'])->name('tenant.concern.store');
    Route::post('/contact-support', [ContactController::class, 'submit'])->name('tenant.contact.submit');
    Route::patch('/concerns/{concern}', [ConcernController::class, 'update'])->name('tenant.concern.update');
    Route::post('/concerns/{concern}/reply', [ConcernController::class, 'reply'])->name('tenant.concern.reply');

    // Public QR Booking Route (no auth required — patients scan QR code)
    Route::get('/book', [BookingController::class, 'create'])->name('tenant.book.create');
    Route::get('/book/check-existing-patient', [BookingController::class, 'checkExistingPatientName'])
        ->name('tenant.book.check-existing-patient');
    Route::post('/book', [BookingController::class, 'store'])
        ->middleware('check.subscription:max_appointments')
        ->name('tenant.book.store');

    // Tenant Auth API Routes (Modal-based login)
    Route::middleware('guest')->group(function () {
        Route::post('/api/login', [TenantAuthController::class, 'store'])->name('tenant.login.store');
        Route::post('/api/login/google', [GoogleAuthController::class, 'handleGoogleLogin'])->name('tenant.login.google');

        // Code-based password reset
        Route::post('/api/password/code/send', [TenantAuthController::class, 'sendResetCode'])->name('tenant.password.send-code');
        Route::post('/api/password/code/verify', [TenantAuthController::class, 'verifyResetCode'])->name('tenant.password.verify-code');
        Route::post('/api/password/code/reset', [TenantAuthController::class, 'resetWithCode'])->name('tenant.password.reset-with-code');

        // Legacy/Traditional reset routes (keeping for compatibility if needed elsewhere)
        Route::post('/api/password/email', [TenantAuthController::class, 'sendResetLink'])->name('tenant.password.email');
        Route::post('/api/password/reset', [TenantAuthController::class, 'resetPassword'])->name('tenant.password.store');
        Route::get('/reset-password/{token}', [ResetPasswordPageController::class, 'show'])->name('tenant.password.reset');
    }
    );

    require __DIR__.'/auth.php';

    // Authenticated Tenant Routes
    // check.subscription (no feature arg) ensures an active subscription exists
    // and shares plan info with Inertia on every authenticated request.
    Route::middleware(['tenant.preview.impersonate', 'auth', 'tenant.session.isolated', 'check.subscription'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('tenant.dashboard');

        // Profile management (all authenticated tenant users)
        Route::get('profile', [ProfileController::class, 'edit'])->name('tenant.profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('tenant.profile.update');
        Route::post('profile/picture', [ProfileController::class, 'updatePicture'])->name('tenant.profile.update-picture');
        Route::delete('profile', [ProfileController::class, 'destroy'])->name('tenant.profile.destroy');

        // Password update (tenant-scoped)
        Route::put('password', [PasswordController::class, 'update'])->name('tenant.password.update');

        Route::post('overage/consent', [SettingsController::class, 'grantOverageConsent'])
            ->name('overage.consent.grant');

        // Staff Settings (personal, permission-gated per section — NOT inside Owner block)
        Route::get('my-settings', [StaffSettingsController::class, 'index'])->name('staff-settings.index');
        Route::put('my-settings/calendar-color', [StaffSettingsController::class, 'updateCalendarColor'])->name('staff-settings.calendar-color');
        Route::put('my-settings/notifications', [StaffSettingsController::class, 'updateNotificationPreferences'])->name('staff-settings.notifications');
        Route::put('my-settings/working-hours', [StaffSettingsController::class, 'updateWorkingHours'])->name('staff-settings.working-hours');

        // Patient management — enforces max_patients limit on create
        // Patient management
        Route::get('patients', [PatientController::class, 'index'])
            ->middleware('permission:view patients')
            ->name('patients.index');

        Route::get('patients/create', [PatientController::class, 'create'])
            ->middleware('permission:create patients')
            ->name('patients.create');

        Route::post('patients', [PatientController::class, 'store'])
            ->middleware(['permission:create patients', 'check.subscription:max_patients'])
            ->name('patients.store');

        Route::get('patients/{patientId}', [PatientController::class, 'show'])
            ->middleware('permission:view patients')
            ->name('patients.show');

        Route::get('patients/{patientId}/pdf', [PatientController::class, 'downloadPdf'])
            ->middleware('permission:view patients')
            ->name('patients.pdf');

        Route::get('patients/{patientId}/edit', [PatientController::class, 'edit'])
            ->middleware('permission:edit patients')
            ->name('patients.edit');

        Route::get('patients/{patientId}/delete', [PatientController::class, 'delete'])
            ->middleware('permission:delete patients')
            ->name('patients.delete');

        Route::put('patients/{patientId}', [PatientController::class, 'update'])
            ->middleware('permission:edit patients')
            ->name('patients.update');

        Route::delete('patients/{patientId}', [PatientController::class, 'destroy'])
            ->middleware('permission:delete patients')
            ->name('patients.destroy');

        // Appointment management — enforces max_appointments limit on create
        // Appointment management
        Route::get('appointments', [AppointmentController::class, 'index'])
            ->middleware('permission:view appointments')
            ->name('appointments.index');

        Route::post('appointments', [AppointmentController::class, 'store'])
            ->middleware(['permission:create appointments', 'check.subscription:max_appointments'])
            ->name('appointments.store');

        Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])
            ->middleware('permission:edit appointments')
            ->name('appointments.update');

        Route::post('appointments/{appointment}/approve', [AppointmentController::class, 'approve'])
            ->middleware('permission:edit appointments')
            ->name('appointments.approve');

        Route::post('appointments/{appointment}/reject', [AppointmentController::class, 'reject'])
            ->middleware('permission:edit appointments')
            ->name('appointments.reject');

        Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])
            ->middleware('permission:delete appointments')
            ->name('appointments.destroy');

        // Progress notes (stored under treatment records)
        // Authorization is enforced in TreatmentController with Owner override.
        Route::get('treatments', [TreatmentController::class, 'index'])->name('treatments.index');
        Route::get('treatments/options', [TreatmentController::class, 'options'])->name('treatments.options');
        Route::post('treatments', [TreatmentController::class, 'store'])->name('treatments.store');
        Route::get('treatments/{treatment}', [TreatmentController::class, 'show'])->name('treatments.show');
        Route::put('treatments/{treatment}', [TreatmentController::class, 'update'])->name('treatments.update');
        Route::delete('treatments/{treatment}', [TreatmentController::class, 'destroy'])->name('treatments.destroy');

        // Medical records (master checklist for booking and clinical intake)
        Route::middleware(['permission:view medical records'])->group(function () {
            Route::get('medical-records', [MedicalRecordController::class, 'index'])->name('medical-records.index');
            Route::post('medical-records', [MedicalRecordController::class, 'store'])->middleware('permission:create medical records')->name('medical-records.store');
            Route::get('medical-records/{medicalRecord}', [MedicalRecordController::class, 'show'])->name('medical-records.show');
            Route::put('medical-records/{medicalRecord}', [MedicalRecordController::class, 'update'])->middleware('permission:edit medical records')->name('medical-records.update');
            Route::delete('medical-records/{medicalRecord}', [MedicalRecordController::class, 'destroy'])->middleware('permission:delete medical records')->name('medical-records.destroy');
});

        // Billing
        // IMPORTANT: View permission must not imply create/edit access.
        Route::middleware(['permission:view billing'])->group(function () {
            Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
        });
        Route::post('billing', [BillingController::class, 'store'])
            ->middleware(['permission:create billing'])
            ->name('billing.store');
        Route::put('billing/{invoice}', [BillingController::class, 'update'])
            ->middleware(['permission:edit billing'])
            ->name('billing.update');

        // Staff management — permission based for delegated access
        Route::get('staff', [StaffController::class, 'index'])
            ->middleware('permission:view staff')
            ->name('staff.index');
        Route::post('staff', [StaffController::class, 'store'])
            ->middleware(['permission:create staff', 'check.subscription:max_users'])
            ->name('staff.store');
        Route::put('staff/{staff}', [StaffController::class, 'update'])
            ->middleware('permission:edit staff')
            ->name('staff.update');
        Route::delete('staff/{staff}', [StaffController::class, 'destroy'])
            ->middleware('permission:delete staff')
            ->name('staff.destroy');
        Route::post('staff/bulk-permissions', [StaffController::class, 'bulkUpdatePermissions'])
            ->middleware('permission:edit staff')
            ->name('staff.bulk-update-permissions');
        Route::post('staff/default-permissions', [StaffController::class, 'updateDefaultPermissions'])
            ->middleware(['permission:edit staff'])
            ->name('staff.default-permissions.update');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])
            ->middleware('permission:view reports')
            ->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])
            ->middleware('permission:view reports')
            ->name('reports.export');

        // Activity Logs
        Route::get('activity-logs', [ActivityLogController::class, 'index'])
            ->middleware('permission:view activity logs')
            ->name('activity-logs.index');

        // Tenant governance routes (delegatable via explicit permissions)
        Route::middleware([])->group(function () {
            // Analytics (Ultimate only)
            Route::get('analytics', [AnalyticsController::class, 'index'])
                ->name('analytics.index')
                ->middleware('permission:view analytics')
                ->middleware('check.subscription:advanced_analytics');

            // Branches (Ultimate only)
            Route::middleware(['permission:manage branches', 'check.subscription:multi_branch'])->group(function () {
                Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
                Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
                Route::put('branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
                Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
                Route::post('branches/switch', [BranchController::class, 'switchBranch'])->name('branches.switch');
            }
            );

            // Settings
            Route::get('settings', [SettingsController::class, 'index'])
                ->middleware('permission:manage settings')
                ->name('settings.index');
            Route::get('settings/configuration', [SettingsController::class, 'configuration'])
                ->middleware('permission:manage security settings')
                ->middleware('check.subscription:security_settings')
                ->name('settings.configuration');
            Route::post('settings/login-lock', [SettingsController::class, 'updateLoginLockSettings'])
                ->middleware('permission:manage security settings')
                ->name('settings.login-lock.update');
            // Settings - Features
            Route::get('settings/features', [SettingsController::class, 'features'])
                ->name('settings.features')
                ->middleware('permission:manage system features')
                ->middleware('check.subscription:custom_system_features');

            // Settings - Updates (OTA)
            // Match sidebar: Owners see Updates; staff need explicit permission.
            Route::get('settings/updates', [SettingsController::class, 'updates'])
                ->middleware('role_or_permission:Owner|manage system updates')
                ->name('settings.updates');
            Route::post('settings/updates/apply', [SettingsController::class, 'applyUpdates'])
                ->middleware('role_or_permission:Owner|manage system updates')
                ->name('settings.updates.apply');
            Route::get('settings/updates/check', [SettingsController::class, 'checkUpdates'])
                ->middleware('role_or_permission:Owner|manage system updates')
                ->name('settings.updates.check');

            // System Update Core API endpoints
            Route::get('api/system/update-status', [SystemUpdateController::class, 'getStatus'])
                ->middleware('role_or_permission:Owner|manage system updates')
                ->name('api.system.update-status');
            Route::post('api/system/update', [SystemUpdateController::class, 'update'])
                ->middleware('role_or_permission:Owner|manage system updates')
                ->name('api.system.update');
            Route::post('api/system/update-files', [SystemUpdateController::class, 'updateFiles'])
                ->middleware('role_or_permission:Owner|manage system updates')
                ->name('api.system.update-files');

            // Stripe Customer Portal — self-service billing (upgrade, downgrade, cancel, update card)
            Route::get('billing-portal', [BillingPortalController::class, 'redirect'])
                ->middleware('permission:access billing portal')
                ->name('billing.portal');

            Route::get('settings/payment-history/{id}/receipt/download', [PaymentHistoryController::class, 'downloadReceipt'])
                ->middleware('permission:manage settings')
                ->name('settings.payment-history.receipt.download');
            Route::get('settings/payment-history/{id}/invoice/download', [PaymentHistoryController::class, 'downloadInvoice'])
                ->middleware('permission:manage settings')
                ->name('settings.payment-history.invoice.download');
        });

        // Custom Branding — permission-based delegation
        Route::middleware(['permission:manage clinic branding', 'check.subscription:custom_branding'])->group(function () {
            Route::get('settings/branding', [SettingsController::class, 'branding'])
                ->name('settings.branding');
            Route::post('settings', [SettingsController::class, 'update'])
                ->name('settings.update');
            Route::post('settings/support-chat-position', [SettingsController::class, 'updateSupportChatPosition'])
                ->name('settings.support-chat-position.update');
            Route::post('settings/logo', [SettingsController::class, 'uploadLogo'])
                ->name('settings.logo.upload');
            Route::delete('settings/logo', [SettingsController::class, 'deleteLogo'])
                ->name('settings.logo.delete');
        }
        );

        // Services — permission based
        Route::get('services', [ServiceController::class, 'index'])
            ->middleware(['permission:view services'])
            ->name('services.index');

        Route::post('services', [ServiceController::class, 'store'])
            ->middleware(['permission:create services'])
            ->name('services.store');

        Route::get('services/{service}', [ServiceController::class, 'show'])
            ->middleware(['permission:view services'])
            ->name('services.show');

        Route::put('services/{service}', [ServiceController::class, 'update'])
            ->middleware(['permission:edit services'])
            ->name('services.update');

        Route::delete('services/{service}', [ServiceController::class, 'destroy'])
            ->middleware(['permission:delete services'])
            ->name('services.destroy');

        // Service approval — delegated via explicit permission
        Route::middleware(['permission:approve services'])->group(function () {
            Route::post('services/{service}/approve', [ServiceController::class, 'approve'])->name('services.approve');
            Route::post('services/{service}/reject', [ServiceController::class, 'reject'])->name('services.reject');
        }
        );

        // In-app notification bell (user-scoped JSON) — not gated on SMS add-on so all plans get a working header.
        Route::get('notifications/recent', [NotificationController::class, 'getRecent'])->name('notifications.recent');
        Route::get('notifications/count', [NotificationController::class, 'getUnreadCount'])->name('notifications.count');

        // Notifications UI & actions (SMS add-on + personal notification settings permission)
        Route::middleware(['check.subscription:sms_notifications', 'permission:manage own notifications'])->group(function () {
            Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::put('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
            Route::put('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
            Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
            Route::get('notifications/settings', [NotificationController::class, 'settings'])->name('notifications.settings');
            Route::put('notifications/settings', [NotificationController::class, 'updateSettings'])->name('notifications.settings.update');
            Route::post('notifications/send-message', [NotificationController::class, 'sendMessage'])->name('notifications.send-message');
        }
        );

        // Support Tickets
        Route::prefix('support')->group(function () {
            Route::get('/', [SupportController::class, 'index'])->name('tenant.support.index');
            Route::post('/', [SupportController::class, 'store'])->name('tenant.support.store');
            Route::get('/{ticket}', [SupportController::class, 'show'])->name('tenant.support.show');
            Route::post('/{ticket}/messages', [SupportController::class, 'sendMessage'])->name('tenant.support.message');
            Route::get('/{ticket}/attachments/{attachment}', [SupportController::class, 'attachment'])->name('tenant.support.attachment');
        }
        );
    }
    );
});
