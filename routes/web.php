<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ContactController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/github/webhook', [\App\Http\Controllers\GitHubWebhookController::class , 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

/* |-------------------------------------------------------------------------- | Central Routes Definition Helper |-------------------------------------------------------------------------- */
$registerCentralRoutes = function ($withNames = false) {
    // Landing Page
    $home = Route::get('/', function () {
            return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'plans' => \App\Models\SubscriptionPlan::orderBy('price_monthly')->get(),
            'googleClientId' => config('services.google.client_id'),
            ]);
        }
        );
        if ($withNames) {
            $home->name('central.home');
        }

        // Contact Form
        $contact = Route::post('/contact', [ContactController::class , 'submit']);
        if ($withNames)
            $contact->name('contact.submit');

        // Registration Routes
        $reg = Route::prefix('registration');
        if ($withNames)
            $reg->name('registration.');
        $reg->group(function () use ($withNames) {
            $v = Route::post('/validate-account', [RegistrationController::class , 'validateAccount']);
            $c = Route::post('/check-subdomain', [RegistrationController::class , 'checkSubdomain']);
            $s = Route::post('/suggest-subdomain', [RegistrationController::class , 'suggestSubdomain']);
            $p = Route::get('/plans', [RegistrationController::class , 'getPlans']);
            $chk = Route::post('/checkout', [RegistrationController::class , 'createCheckoutSession']);
            $succ = Route::get('/success', [RegistrationController::class , 'handleSuccess']);
            $webh = Route::post('/webhook', [RegistrationController::class , 'handleWebhook']);
            $complete = Route::post('/complete', [RegistrationController::class , 'completeRegistration']);
            $pay = Route::get('/pay', [RegistrationController::class , 'showPaymentPage']);
            $receipt = Route::get('/receipt/{session_id}', [RegistrationController::class , 'downloadReceipt']);

            if ($withNames) {
                $v->name('validate');
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
        $access = Route::get('/tenant/{subdomain}', [RegistrationController::class , 'accessTenant']);
        $accessFromSession = Route::get('/tenant-access', [RegistrationController::class , 'accessTenantFromSession']);
        $previewExit = Route::post('/tenant-preview/exit', [RegistrationController::class , 'exitPreview']);
        if ($withNames) {
            $access->name('central.access-tenant');
            $accessFromSession->name('central.tenant-access');
            $previewExit->name('central.preview-exit');
        }

        // Admin Routes
        $admin = Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin');
        if ($withNames)
            $admin->name('admin.');
        $admin->group(function () use ($withNames) {
            $dash = Route::get('/dashboard', [AdminDashboardController::class , 'index']);
            if ($withNames)
                $dash->name('dashboard');

            // Tenant Management
            $tenantsI = Route::get('/tenants', [\App\Http\Controllers\Admin\TenantController::class , 'index']);
            $tenantsS = Route::get('/tenants/{tenant}', [\App\Http\Controllers\Admin\TenantController::class , 'show']);
            $tenantsU = Route::put('/tenants/{tenant}/status', [\App\Http\Controllers\Admin\TenantController::class , 'updateStatus']);
            $tenantsUpd = Route::put('/tenants/{tenant}', [\App\Http\Controllers\Admin\TenantController::class , 'update']);
            $tenantsD = Route::delete('/tenants/{tenant}', [\App\Http\Controllers\Admin\TenantController::class , 'destroy']);
            $tenantsPreview = Route::post('/tenants/{tenant}/preview', [\App\Http\Controllers\Admin\TenantController::class , 'startPreview']);
            $tenantPreviewOpen = Route::get('/tenant-preview/open', [\App\Http\Controllers\Admin\TenantController::class , 'openIsolatedPreview']);
            $tenantPreviewStart = Route::post('/tenant-preview/start', [\App\Http\Controllers\Admin\TenantController::class , 'startIsolatedPreview']);
            $tenantPreviewReset = Route::post('/tenant-preview/reset', [\App\Http\Controllers\Admin\TenantController::class , 'resetIsolatedPreview']);
            $tenantsApprove = Route::post('/tenants/{tenant}/approve', [\App\Http\Controllers\Admin\TenantController::class , 'approveTenant']);
            $tenantsReject = Route::post('/tenants/{tenant}/reject', [\App\Http\Controllers\Admin\TenantController::class , 'rejectTenant']);
            if ($withNames) {
                $tenantsI->name('tenants.index');
                $tenantsS->name('tenants.show');
                $tenantsU->name('tenants.updateStatus');
                $tenantsUpd->name('tenants.update');
                $tenantsD->name('tenants.destroy');
                $tenantsPreview->name('tenants.preview');
                $tenantPreviewOpen->name('tenant-preview.open');
                $tenantPreviewStart->name('tenant-preview.start');
                $tenantPreviewReset->name('tenant-preview.reset');
                $tenantsApprove->name('tenants.approve');
                $tenantsReject->name('tenants.reject');
                Route::get('/tenants/{tenant}/usage', [\App\Http\Controllers\Admin\TenantController::class , 'getUsageStats'])->name('tenants.usage');
            }

            // Pending Registrations Management
            $pendingI = Route::get('/pending-registrations', [\App\Http\Controllers\Admin\PendingRegistrationController::class , 'index']);
            $pendingS = Route::get('/pending-registrations/{pendingRegistration}', [\App\Http\Controllers\Admin\PendingRegistrationController::class , 'show']);
            $pendingA = Route::post('/pending-registrations/{pendingRegistration}/approve', [\App\Http\Controllers\Admin\PendingRegistrationController::class , 'approve']);
            $pendingR = Route::post('/pending-registrations/{pendingRegistration}/reject', [\App\Http\Controllers\Admin\PendingRegistrationController::class , 'reject']);
            $pendingExtend = Route::post('/pending-registrations/{pendingRegistration}/extend', [\App\Http\Controllers\Admin\PendingRegistrationController::class , 'extendTime']);
            $pendingSetTime = Route::post('/pending-registrations/{pendingRegistration}/set-time', [\App\Http\Controllers\Admin\PendingRegistrationController::class , 'setTime']);
            $pendingToggleReminder = Route::post('/pending-registrations/{pendingRegistration}/toggle-reminder', [\App\Http\Controllers\Admin\PendingRegistrationController::class , 'toggleReminder']);
            $pendingToggleAutoApprove = Route::post('/pending-registrations/{pendingRegistration}/toggle-auto-approve', [\App\Http\Controllers\Admin\PendingRegistrationController::class , 'toggleAutoApprove']);

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
            if ($withNames)
                $tenantApi->name('api.tenants.');
            $tenantApi->group(function () use ($withNames) {
                    $preview = Route::post('/preview-database-name', [\App\Http\Controllers\Api\TenantController::class , 'previewDatabaseName']);
                    $list = Route::get('/', [\App\Http\Controllers\Api\TenantController::class , 'index']);
                    $show = Route::get('/{tenant}', [\App\Http\Controllers\Api\TenantController::class , 'show']);
                    $switch = Route::post('/{tenant}/switch', [\App\Http\Controllers\Api\TenantController::class , 'switchDatabase']);
                    $store = Route::post('/', [\App\Http\Controllers\Api\TenantController::class , 'store']);
                    $update = Route::put('/{tenant}', [\App\Http\Controllers\Api\TenantController::class , 'update']);
                    $destroy = Route::delete('/{tenant}', [\App\Http\Controllers\Api\TenantController::class , 'destroy']);

                    if ($withNames) {
                        $preview->name('preview-database-name');
                        $list->name('list');
                        $show->name('show');
                        $switch->name('switch');
                        $store->name('store');
                        $update->name('update');
                        $destroy->name('destroy');
                    }
                }
                );

                // Subscription Plans
                $plansI = Route::get('/plans', [\App\Http\Controllers\Admin\PlanController::class , 'index']);
                $plansS = Route::post('/plans', [\App\Http\Controllers\Admin\PlanController::class , 'store']);
                $plansU = Route::put('/plans/{plan}', [\App\Http\Controllers\Admin\PlanController::class , 'update']);
                $plansD = Route::delete('/plans/{plan}', [\App\Http\Controllers\Admin\PlanController::class , 'destroy']);
                $plansF = Route::post('/plans/{plan}/force-sync', [\App\Http\Controllers\Admin\PlanController::class , 'forceSync']);
                $plansP = Route::post('/plans/{plan}/push-updates', [\App\Http\Controllers\Admin\PlanController::class , 'pushUpdates']);
                if ($withNames) {
                    $plansI->name('plans.index');
                    $plansS->name('plans.store');
                    $plansU->name('plans.update');
                    $plansD->name('plans.destroy');
                    $plansF->name('plans.force-sync');
                    $plansP->name('plans.push-updates');
                    Route::post('/plans/batch-push-updates', [\App\Http\Controllers\Admin\PlanController::class , 'batchPushUpdates'])->name('plans.batch-push-updates');
                }

                // Subscriptions
                $subsI = Route::get('/subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class , 'index']);
                if ($withNames)
                    $subsI->name('subscriptions.index');

                // Billing & Revenue
                $revI = Route::get('/revenue', [\App\Http\Controllers\Admin\RevenueController::class , 'index']);
                if ($withNames)
                    $revI->name('revenue.index');

                // Audit Logs
                $auditI = Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class , 'index']);
                if ($withNames)
                    $auditI->name('audit-logs.index');

                // Platform Analytics
                $analyticsI = Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class , 'index']);
                if ($withNames)
                    $analyticsI->name('analytics.index');

                // Feature Management
                $featuresI = Route::get('/features', [FeatureController::class , 'index']);
                $featuresAdoption = Route::get('/features/adoption', [FeatureController::class , 'adoption']);
                $featuresS = Route::post('/features', [FeatureController::class , 'store']);
                $featuresU = Route::put('/features/{feature}', [FeatureController::class , 'update']);
                $featuresD = Route::delete('/features/{feature}', [FeatureController::class , 'destroy']);
                $featuresT = Route::put('/features/{feature}/toggle', [FeatureController::class , 'toggleActive']);
                $featuresA = Route::post('/features/{feature}/assign', [FeatureController::class , 'assignToPlan']);
                $featuresR = Route::delete('/features/{feature}/remove', [FeatureController::class , 'removeFromPlan']);
                if ($withNames) {
                    $featuresI->name('features.index');
                    $featuresAdoption->name('features.adoption');
                    $featuresS->name('features.store');
                    $featuresU->name('features.update');
                    $featuresD->name('features.destroy');
                    $featuresT->name('features.toggle');
                    $featuresA->name('features.assign');
                    $featuresR->name('features.remove');
                    Route::post('/features/sync-all', [FeatureController::class , 'syncAllUpdates'])->name('features.sync-all');
                    Route::get('/features/batch/{batchId}', [FeatureController::class , 'getBatchStatus'])->name('features.batch-status');
                }

                // Support & Tickets
                $supportI = Route::get('/support', [\App\Http\Controllers\Admin\SupportTicketController::class , 'index']);
                $supportS = Route::get('/support/{ticket}', [\App\Http\Controllers\Admin\SupportTicketController::class , 'show']);
                $supportR = Route::post('/support/{ticket}/reply', [\App\Http\Controllers\Admin\SupportTicketController::class , 'reply']);
                $supportU = Route::put('/support/{ticket}/status', [\App\Http\Controllers\Admin\SupportTicketController::class , 'updateStatus']);
                $supportD = Route::delete('/support/{ticket}', [\App\Http\Controllers\Admin\SupportTicketController::class , 'destroy']);
                if ($withNames) {
                    $supportI->name('support.index');
                    $supportS->name('support.show');
                    $supportR->name('support.reply');
                    $supportU->name('support.updateStatus');
                    $supportD->name('support.destroy');
                }

                // Notifications
                $notifI = Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationController::class , 'index']);
                $notifRec = Route::get('/notifications/recent', [\App\Http\Controllers\Admin\NotificationController::class , 'getRecent']);
                $notifCount = Route::get('/notifications/count', [\App\Http\Controllers\Admin\NotificationController::class , 'getUnreadCount']);
                $notifMark = Route::put('/notifications/{id}/read', [\App\Http\Controllers\Admin\NotificationController::class , 'markAsRead']);
                $notifMarkAll = Route::put('/notifications/read-all', [\App\Http\Controllers\Admin\NotificationController::class , 'markAllAsRead']);
                $notifD = Route::delete('/notifications/{id}', [\App\Http\Controllers\Admin\NotificationController::class , 'destroy']);
                $notifSettings = Route::get('/notifications/settings', [\App\Http\Controllers\Admin\NotificationController::class , 'settings']);
                $notifSettingsU = Route::put('/notifications/settings', [\App\Http\Controllers\Admin\NotificationController::class , 'updateSettings']);
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
                $sysSetI = Route::get('/system-settings', [SystemSettingsController::class , 'index']);
                $sysSetU = Route::post('/system-settings', [SystemSettingsController::class , 'update']);
                $sysSetUG = Route::post('/system-settings/group/{group}', [SystemSettingsController::class , 'updateByGroup']);
                $sysSetT = Route::post('/system-settings/toggle', [SystemSettingsController::class , 'toggle']);
                $sysSetLogoU = Route::post('/system-settings/logo/upload', [SystemSettingsController::class , 'uploadLogo']);
                $sysSetLogoD = Route::delete('/system-settings/logo/delete', [SystemSettingsController::class , 'deleteLogo']);
                if ($withNames) {
                    $sysSetI->name('system-settings.index');
                    $sysSetU->name('system-settings.update');
                    $sysSetUG->name('system-settings.update-group');
                    $sysSetT->name('system-settings.toggle');
                    $sysSetLogoU->name('system-settings.logo.upload');
                    $sysSetLogoD->name('system-settings.logo.delete');
                }
            }
            );

            // Profile Routes
            Route::middleware('auth')->group(function () use ($withNames) {
            $edit = Route::get('/profile', [ProfileController::class , 'edit']);
            $update = Route::patch('/profile', [ProfileController::class , 'update']);
            $updatePict = Route::post('/profile/picture', [ProfileController::class , 'updatePicture']);
            $dest = Route::delete('/profile', [ProfileController::class , 'destroy']);

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
                    Route::post('login', [AuthenticatedSessionController::class , 'store'])->name('login');
                    Route::post('login/google', [\App\Http\Controllers\Auth\GoogleAuthController::class , 'handleGoogleLogin'])->name('admin.login.google');
                    Route::post('register', [RegisteredUserController::class , 'store'])->name('register');
                }
                );

                Route::middleware('auth')->group(function () {
                    Route::post('logout', [AuthenticatedSessionController::class , 'destroy'])->name('logout');
                }
                );
            }
        };

/* |-------------------------------------------------------------------------- | Group A: Central Domain (Primary & Aliases) |-------------------------------------------------------------------------- */
$centralDomains = config('tenancy.central_domains', ['lvh.me', 'localhost', '127.0.0.1']);

// Determine primary domain based on APP_URL to ensure named routes generate the correct host
$appDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
$primary = collect($centralDomains)->contains($appDomain) ? $appDomain : (collect($centralDomains)->contains('lvh.me') ? 'lvh.me' : ($centralDomains[0] ?? 'localhost'));

$others = array_filter($centralDomains, fn($d) => $d !== $primary);

// Primary Domain with names
Route::domain($primary)->group(fn() => $registerCentralRoutes(true));

// Alias Domains without names (prevents collision)
foreach ($others as $alias) {
    Route::domain($alias)->group(fn() => $registerCentralRoutes(false));
}
