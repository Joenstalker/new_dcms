<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\GoogleDriveController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PendingRegistrationController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\VersionComplianceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\GitHubWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\VerifyGitHubWebhookSignature;
use App\Http\Middleware\VerifyStripeWebhookSignature;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/github/webhook', [GitHubWebhookController::class, 'handle'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->middleware([VerifyGitHubWebhookSignature::class, 'throttle:60,1']);

/* |-------------------------------------------------------------------------- | Central Routes Definition Helper |-------------------------------------------------------------------------- */
$registerCentralRoutes = function ($withNames = false) {
    // Landing Page
    $home = Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'plans' => SubscriptionPlan::orderBy('price_monthly')->get(),
            'googleClientId' => config('services.google.client_id'),
        ]);
    }
    );
    if ($withNames) {
        $home->name('central.home');
    }

    // Contact Form
    $contact = Route::post('/contact', [ContactController::class, 'submit']);
    if ($withNames) {
        $contact->name('contact.submit');
    }

    // Registration Routes
    $reg = Route::prefix('registration');
    if ($withNames) {
        $reg->name('registration.');
    }
    $reg->group(function () use ($withNames) {
        $v = Route::post('/validate-account', [RegistrationController::class, 'validateAccount']);
        $e = Route::post('/check-email', [RegistrationController::class, 'checkEmail']);
        $c = Route::post('/check-subdomain', [RegistrationController::class, 'checkSubdomain']);
        $s = Route::post('/suggest-subdomain', [RegistrationController::class, 'suggestSubdomain']);
        $p = Route::get('/plans', [RegistrationController::class, 'getPlans']);
        $chk = Route::post('/checkout', [RegistrationController::class, 'createCheckoutSession']);
        $succ = Route::get('/success', [RegistrationController::class, 'handleSuccess']);
        $webh = Route::post('/webhook', [RegistrationController::class, 'handleWebhook'])
            ->middleware([VerifyStripeWebhookSignature::class, 'throttle:60,1']);
        $complete = Route::post('/complete', [RegistrationController::class, 'completeRegistration']);
        $pay = Route::get('/pay', [RegistrationController::class, 'showPaymentPage']);
        $receipt = Route::get('/receipt/{session_id}', [RegistrationController::class, 'downloadReceipt']);

        if ($withNames) {
            $v->name('validate');
            $e->name('check-email');
            $c->name('check-subdomain');
            $s->name('suggest-subdomain');
            $p->name('plans');
            $chk->name('checkout');
            $succ->name('success');
            $webh->name('webhook');
            $complete->name('complete');
            $pay->name('pay');
            $receipt->name('receipt');
        }
    }
    );

    // Fallback routes
    $access = Route::get('/tenant/{subdomain}', [RegistrationController::class, 'accessTenant']);
    $accessFromSession = Route::get('/tenant-access', [RegistrationController::class, 'accessTenantFromSession']);
    $previewExit = Route::post('/tenant-preview/exit', [RegistrationController::class, 'exitPreview']);
    if ($withNames) {
        $access->name('central.access-tenant');
        $accessFromSession->name('central.tenant-access');
        $previewExit->name('central.preview-exit');
    }

    // Admin Routes
    $admin = Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin');
    if ($withNames) {
        $admin->name('admin.');
    }
    $admin->group(function () use ($withNames) {
        $dash = Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        if ($withNames) {
            $dash->name('dashboard');
        }

        // Tenant Management
        $tenantsI = Route::get('/tenants', [TenantController::class, 'index']);
        $tenantsS = Route::get('/tenants/{tenant}', [TenantController::class, 'show']);
        $tenantsU = Route::put('/tenants/{tenant}/status', [TenantController::class, 'updateStatus']);
        $tenantsUpd = Route::put('/tenants/{tenant}', [TenantController::class, 'update']);
        $tenantsD = Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy']);
        $tenantsPreview = Route::post('/tenants/{tenant}/preview', [TenantController::class, 'startPreview']);
        $tenantsSettingsUpdate = Route::post('/tenants/settings', [TenantController::class, 'updateClinicSettings']);
        $tenantPreviewOpen = Route::get('/tenant-preview/open', [TenantController::class, 'openIsolatedPreview']);
        $tenantPreviewStart = Route::post('/tenant-preview/start', [TenantController::class, 'startIsolatedPreview']);
        $tenantPreviewReset = Route::post('/tenant-preview/reset', [TenantController::class, 'resetIsolatedPreview']);
        $tenantsApprove = Route::post('/tenants/{tenant}/approve', [TenantController::class, 'approveTenant']);
        $tenantsReject = Route::post('/tenants/{tenant}/reject', [TenantController::class, 'rejectTenant']);
        if ($withNames) {
            $tenantsI->name('tenants.index');
            $tenantsS->name('tenants.show');
            $tenantsU->name('tenants.updateStatus');
            $tenantsUpd->name('tenants.update');
            $tenantsD->name('tenants.destroy');
            $tenantsPreview->name('tenants.preview');
            $tenantsSettingsUpdate->name('tenants.settings.update');
            $tenantPreviewOpen->name('tenant-preview.open');
            $tenantPreviewStart->name('tenant-preview.start');
            $tenantPreviewReset->name('tenant-preview.reset');
            $tenantsApprove->name('tenants.approve');
            $tenantsReject->name('tenants.reject');
            Route::get('/tenants/{tenant}/usage', [TenantController::class, 'getUsageStats'])->name('tenants.usage');
        }

        // Pending Registrations Management
        $pendingI = Route::get('/pending-registrations', [PendingRegistrationController::class, 'index']);
        $pendingS = Route::get('/pending-registrations/{pendingRegistration}', [PendingRegistrationController::class, 'show']);
        $pendingA = Route::post('/pending-registrations/{pendingRegistration}/approve', [PendingRegistrationController::class, 'approve']);
        $pendingR = Route::post('/pending-registrations/{pendingRegistration}/reject', [PendingRegistrationController::class, 'reject']);
        $pendingExtend = Route::post('/pending-registrations/{pendingRegistration}/extend', [PendingRegistrationController::class, 'extendTime']);
        $pendingSetTime = Route::post('/pending-registrations/{pendingRegistration}/set-time', [PendingRegistrationController::class, 'setTime']);
        $pendingToggleReminder = Route::post('/pending-registrations/{pendingRegistration}/toggle-reminder', [PendingRegistrationController::class, 'toggleReminder']);
        $pendingToggleAutoApprove = Route::post('/pending-registrations/{pendingRegistration}/toggle-auto-approve', [PendingRegistrationController::class, 'toggleAutoApprove']);

        if ($withNames) {
            $pendingI->name('pending-registrations.index');
            $pendingS->name('pending-registrations.show');
            $pendingA->name('pending-registrations.approve');
            $pendingR->name('pending-registrations.reject');
            $pendingExtend->name('pending-registrations.extend');
            $pendingSetTime->name('pending-registrations.set-time');
            $pendingToggleReminder->name('pending-registrations.toggle-reminder');
            $pendingToggleAutoApprove->name('pending-registrations.toggle-auto-approve');
        }

        // Tenant API Routes (for database name preview)
        $tenantApi = Route::prefix('api/tenants');
        if ($withNames) {
            $tenantApi->name('api.tenants.');
        }
        $tenantApi->group(function () use ($withNames) {
            $list = Route::get('/', [App\Http\Controllers\Api\TenantController::class, 'index']);
            $show = Route::get('/{tenant}', [App\Http\Controllers\Api\TenantController::class, 'show']);
            $switch = Route::post('/{tenant}/switch', [App\Http\Controllers\Api\TenantController::class, 'switchDatabase']);
            $update = Route::put('/{tenant}', [App\Http\Controllers\Api\TenantController::class, 'update']);
            $destroy = Route::delete('/{tenant}', [App\Http\Controllers\Api\TenantController::class, 'destroy']);

            if ($withNames) {
                $list->name('list');
                $show->name('show');
                $switch->name('switch');
                $update->name('update');
                $destroy->name('destroy');
            }
        }
        );

        // Subscription Plans
        $plansI = Route::get('/plans', [PlanController::class, 'index']);
        $plansS = Route::post('/plans', [PlanController::class, 'store']);
        $plansU = Route::put('/plans/{plan}', [PlanController::class, 'update']);
        $plansD = Route::delete('/plans/{plan}', [PlanController::class, 'destroy']);
        $plansF = Route::post('/plans/{plan}/force-sync', [PlanController::class, 'forceSync']);
        $plansP = Route::post('/plans/{plan}/push-updates', [PlanController::class, 'pushUpdates']);
        if ($withNames) {
            $plansI->name('plans.index');
            $plansS->name('plans.store');
            $plansU->name('plans.update');
            $plansD->name('plans.destroy');
            $plansF->name('plans.force-sync');
            $plansP->name('plans.push-updates');
            Route::post('/plans/batch-push-updates', [PlanController::class, 'batchPushUpdates'])->name('plans.batch-push-updates');
        }

        // Subscriptions
        $subsI = Route::get('/subscriptions', [SubscriptionController::class, 'index']);
        if ($withNames) {
            $subsI->name('subscriptions.index');
        }

        // Billing & Revenue
        $revI = Route::get('/revenue', [RevenueController::class, 'index']);
        if ($withNames) {
            $revI->name('revenue.index');
        }

        // Audit Logs
        $auditI = Route::get('/audit-logs', [AuditLogController::class, 'index']);
        if ($withNames) {
            $auditI->name('audit-logs.index');
        }

        // Platform Analytics
        $analyticsI = Route::get('/analytics', [AnalyticsController::class, 'index']);
        if ($withNames) {
            $analyticsI->name('analytics.index');
        }

        // Version Compliance
        $versionComplianceI = Route::get('/version-compliance', [VersionComplianceController::class, 'index']);
        $versionComplianceToggle = Route::post('/version-compliance/auto-rollout', [VersionComplianceController::class, 'toggleAutoRollout']);
        $versionComplianceTrigger = Route::post('/version-compliance/rollout', [VersionComplianceController::class, 'triggerRollout']);
        if ($withNames) {
            $versionComplianceI->name('version-compliance.index');
        }
        if ($withNames) {
            $versionComplianceToggle->name('version-compliance.auto-rollout');
            $versionComplianceTrigger->name('version-compliance.rollout');
        }

        // Feature Management
        $featuresI = Route::get('/features', [FeatureController::class, 'index']);
        $featuresS = Route::post('/features', [FeatureController::class, 'store']);
        $featuresU = Route::put('/features/{feature}', [FeatureController::class, 'update']);
        $featuresD = Route::delete('/features/{feature}', [FeatureController::class, 'destroy']);
        $featuresT = Route::put('/features/{feature}/toggle', [FeatureController::class, 'toggleActive']);
        $featuresTA = Route::put('/features/{feature}/toggle-active', [FeatureController::class, 'toggleActive']);
        $featuresArchive = Route::post('/features/{feature}/archive', [FeatureController::class, 'archive']);
        $featuresRestore = Route::post('/features/{feature}/restore', [FeatureController::class, 'restore']);
        $featuresA = Route::post('/features/{feature}/assign', [FeatureController::class, 'assignToPlan']);
        $featuresR = Route::delete('/features/{feature}/remove', [FeatureController::class, 'removeFromPlan']);
        if ($withNames) {
            $featuresI->name('features.index');
            $featuresS->name('features.store');
            $featuresU->name('features.update');
            $featuresD->name('features.destroy');
            $featuresT->name('features.toggle');
            $featuresTA->name('features.toggle-active');
            $featuresArchive->name('features.archive');
            $featuresRestore->name('features.restore');
            $featuresA->name('features.assign');
            $featuresR->name('features.remove');
            Route::post('/features/sync-all', [FeatureController::class, 'syncAllUpdates'])->name('features.sync-all');
            Route::get('/features/batch/{batchId}', [FeatureController::class, 'getBatchStatus'])->name('features.batch-status');
        }

        // Support & Tickets
        $supportI = Route::get('/support', [SupportTicketController::class, 'index']);
        $supportS = Route::get('/support/{ticket}', [SupportTicketController::class, 'show']);
        $supportR = Route::post('/support/{ticket}/reply', [SupportTicketController::class, 'reply']);
        $supportU = Route::put('/support/{ticket}/status', [SupportTicketController::class, 'updateStatus']);
        $supportD = Route::delete('/support/{ticket}', [SupportTicketController::class, 'destroy']);
        $supportAtt = Route::get('/support/{ticket}/attachments/{attachment}', [SupportTicketController::class, 'attachment']);
        if ($withNames) {
            $supportI->name('support.index');
            $supportS->name('support.show');
            $supportR->name('support.reply');
            $supportU->name('support.updateStatus');
            $supportD->name('support.destroy');
            $supportAtt->name('support.attachment');
        }

        // Notifications
        $notifI = Route::get('/notifications', [NotificationController::class, 'index']);
        $notifRec = Route::get('/notifications/recent', [NotificationController::class, 'getRecent']);
        $notifCount = Route::get('/notifications/count', [NotificationController::class, 'getUnreadCount']);
        $notifMark = Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        $notifMarkAll = Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        $notifD = Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
        $notifSettings = Route::get('/notifications/settings', [NotificationController::class, 'settings']);
        $notifSettingsU = Route::put('/notifications/settings', [NotificationController::class, 'updateSettings']);
        if ($withNames) {
            $notifI->name('notifications.index');
            $notifRec->name('notifications.recent');
            $notifCount->name('notifications.count');
            $notifMark->name('notifications.mark-read');
            $notifMarkAll->name('notifications.mark-all-read');
            $notifD->name('notifications.destroy');
            $notifSettings->name('notifications.settings');
            $notifSettingsU->name('notifications.settings.update');
        }

        // System Settings
        $sysSetI = Route::get('/system-settings', [SystemSettingsController::class, 'index']);
        $sysSetU = Route::post('/system-settings', [SystemSettingsController::class, 'update']);
        $sysSetUG = Route::post('/system-settings/group/{group}', [SystemSettingsController::class, 'updateByGroup']);
        $sysSetT = Route::post('/system-settings/toggle', [SystemSettingsController::class, 'toggle']);
        $sysSetLogoU = Route::post('/system-settings/logo/upload', [SystemSettingsController::class, 'uploadLogo']);
        $sysSetLogoD = Route::delete('/system-settings/logo/delete', [SystemSettingsController::class, 'deleteLogo']);
        // Backup routes
        $backupIndex = Route::get('/system-settings/backup', [SystemSettingsController::class, 'backupIndex']);
        $backupRun = Route::post('/system-settings/backup/run', [SystemSettingsController::class, 'runBackup']);
        $backupUpdate = Route::post('/system-settings/backup/settings', [SystemSettingsController::class, 'updateBackupSettings']);
        if ($withNames) {
            $sysSetI->name('system-settings.index');
            $sysSetU->name('system-settings.update');
            $sysSetUG->name('system-settings.update-group');
            $sysSetT->name('system-settings.toggle');
            $sysSetLogoU->name('system-settings.logo.upload');
            $sysSetLogoD->name('system-settings.logo.delete');
            $backupIndex->name('system-settings.backup.index');
            $backupRun->name('system-settings.backup.run');
            $backupUpdate->name('system-settings.backup.update');
        }

        // Google Drive Backup
        $driveConnect = Route::get('/drive/connect', [GoogleDriveController::class, 'connect']);
        $driveCallback = Route::get('/drive/callback', [GoogleDriveController::class, 'callback']);
        $driveDisconnect = Route::post('/drive/disconnect', [GoogleDriveController::class, 'disconnect']);
        $driveStatus = Route::get('/drive/status', [GoogleDriveController::class, 'status']);
        if ($withNames) {
            $driveConnect->name('drive.connect');
            $driveCallback->name('drive.callback');
            $driveDisconnect->name('drive.disconnect');
            $driveStatus->name('drive.status');
        }
    }
    );

    // Profile Routes
    Route::middleware('auth')->group(function () use ($withNames) {
        $edit = Route::get('/profile', [ProfileController::class, 'edit']);
        $update = Route::patch('/profile', [ProfileController::class, 'update']);
        $updatePict = Route::post('/profile/picture', [ProfileController::class, 'updatePicture']);
        $dest = Route::delete('/profile', [ProfileController::class, 'destroy']);

        if ($withNames) {
            $edit->name('profile.edit');
            $update->name('profile.update');
            $updatePict->name('profile.update-picture');
            $dest->name('profile.destroy');
        }
    }
    );

    // Auth Routes
    // For central domain, we only want POST routes for the login modal
    // and we don't want the GET routes (login, register, etc.) to avoid clashing or appearing.
    if ($withNames) {
        Route::middleware('guest')->group(function () {
            Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login');
            Route::post('login/google', [GoogleAuthController::class, 'handleGoogleLogin'])->name('admin.login.google');
            Route::post('register', [RegisteredUserController::class, 'store'])->name('register');
        }
        );

        Route::middleware('auth')->group(function () {
            Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        }
        );
    }
};

/* |-------------------------------------------------------------------------- | Group A: Central Domain (Primary & Aliases) |-------------------------------------------------------------------------- */
$centralDomains = config('tenancy.central_domains', ['lvh.me', 'localhost', '127.0.0.1']);

// Determine primary domain based on APP_URL to ensure named routes generate the correct host
$appDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
$primary = collect($centralDomains)->contains($appDomain) ? $appDomain : (collect($centralDomains)->contains('lvh.me') ? 'lvh.me' : ($centralDomains[0] ?? 'localhost'));

$others = array_filter($centralDomains, fn ($d) => $d !== $primary);

// Primary Domain with names
Route::domain($primary)->group(fn () => $registerCentralRoutes(true));

// Alias Domains without names (prevents collision)
foreach ($others as $alias) {
    Route::domain($alias)->group(fn () => $registerCentralRoutes(false));
}
