<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/* |-------------------------------------------------------------------------- | Central Routes Definition Helper |-------------------------------------------------------------------------- */
$registerCentralRoutes = function ($withNames = false) {
    // Landing Page
    $home = Route::get('/', function () {
            return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'plans' => \App\Models\SubscriptionPlan::orderBy('price_monthly')->get(),
            ]);
        }
        );
        if ($withNames)
            $home->name('central.home');

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

            if ($withNames) {
                $v->name('validate');
                $c->name('check-subdomain');
                $s->name('suggest-subdomain');
                $p->name('plans');
                $chk->name('checkout');
                $succ->name('success');
                $webh->name('webhook');
            }
        }
        );

        // Fallback routes
        $access = Route::get('/tenant/{subdomain}', [RegistrationController::class , 'accessTenant']);
        $accessFromSession = Route::get('/tenant-access', [RegistrationController::class , 'accessTenantFromSession']);
        if ($withNames) {
            $access->name('central.access-tenant');
            $accessFromSession->name('central.tenant-access');
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
            if ($withNames) {
                $tenantsI->name('tenants.index');
                $tenantsS->name('tenants.show');
                $tenantsU->name('tenants.updateStatus');
            }

            // Subscription Plans
            $plansI = Route::get('/plans', [\App\Http\Controllers\Admin\PlanController::class , 'index']);
            $plansS = Route::post('/plans', [\App\Http\Controllers\Admin\PlanController::class , 'store']);
            $plansU = Route::put('/plans/{plan}', [\App\Http\Controllers\Admin\PlanController::class , 'update']);
            $plansD = Route::delete('/plans/{plan}', [\App\Http\Controllers\Admin\PlanController::class , 'destroy']);
            $plansF = Route::post('/plans/{plan}/force-sync', [\App\Http\Controllers\Admin\PlanController::class , 'forceSync']);
            if ($withNames) {
                $plansI->name('plans.index');
                $plansS->name('plans.store');
                $plansU->name('plans.update');
                $plansD->name('plans.destroy');
                $plansF->name('plans.force-sync');
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
        }
        );

        // Profile Routes
        Route::middleware('auth')->group(function () use ($withNames) {
            $edit = Route::get('/profile', [ProfileController::class , 'edit']);
            $update = Route::patch('/profile', [ProfileController::class , 'update']);
            $dest = Route::delete('/profile', [ProfileController::class , 'destroy']);
            if ($withNames) {
                $edit->name('profile.edit');
                $update->name('profile.update');
                $dest->name('profile.destroy');
            }
        }
        );

        // Auth Routes
        // ONLY names for primary domain
        if ($withNames) {
            require __DIR__ . '/auth.php';
        }
        else {
        // Alias domains don't get the named auth routes to avoid clashing
        // If someone goes to localhost/login, it will still match if we define them unnamed,
        // but it's simpler to just require it and NOT assign names.
        // However, require __DIR__.'/auth.php' includes names.
        // So we just skip it for aliases.
        }
    };

/* |-------------------------------------------------------------------------- | Group A: Central Domain (Primary & Aliases) |-------------------------------------------------------------------------- */
$centralDomains = config('tenancy.central_domains', ['lvh.me', 'localhost', '127.0.0.1']);
// We find lvh.me if possible, otherwise first one
$primary = collect($centralDomains)->contains('lvh.me') ? 'lvh.me' : ($centralDomains[0] ?? 'localhost');
$others = array_filter($centralDomains, fn($d) => $d !== $primary);

// Primary Domain with names
Route::domain($primary)->group(fn() => $registerCentralRoutes(true));

// Alias Domains without names (prevents collision)
foreach ($others as $alias) {
    Route::domain($alias)->group(fn() => $registerCentralRoutes(false));
}
