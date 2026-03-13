<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
    'plans' => \App\Models\SubscriptionPlan::orderBy('price_monthly')->get(),
    ]);
});

// Registration Routes (Public)
Route::prefix('registration')->group(function () {
    // Step 1: Validate account setup
    Route::post('/validate-account', [RegistrationController::class , 'validateAccount']);

    // Step 2: Check subdomain availability
    Route::post('/check-subdomain', [RegistrationController::class , 'checkSubdomain']);

    // Step 2: Get subdomain suggestions
    Route::post('/suggest-subdomain', [RegistrationController::class , 'suggestSubdomain']);

    // Get plans for payment modal
    Route::get('/plans', [RegistrationController::class , 'getPlans']);

    // Create Stripe checkout session
    Route::post('/checkout', [RegistrationController::class , 'createCheckoutSession']);

    // Handle successful payment
    Route::get('/success', [RegistrationController::class , 'handleSuccess']);

    // Webhook for Stripe
    Route::post('/webhook', [RegistrationController::class , 'handleWebhook']);
});

// Fallback route for local development without wildcard DNS
// This allows accessing tenant via /tenant/{subdomain} instead of {subdomain}.dcms.test
Route::get('/tenant/{subdomain}', [RegistrationController::class , 'accessTenant']);

// Also handle the fallback_url if stored in session
Route::get('/tenant-access', [RegistrationController::class , 'accessTenantFromSession']);

// Admin Routes
Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class , 'index'])->name('dashboard');

    // Tenant Management
    Route::get('/tenants', [\App\Http\Controllers\Admin\TenantController::class , 'index'])->name('tenants.index');
    Route::get('/tenants/{tenant}', [\App\Http\Controllers\Admin\TenantController::class , 'show'])->name('tenants.show');
    Route::put('/tenants/{tenant}/status', [\App\Http\Controllers\Admin\TenantController::class , 'updateStatus'])->name('tenants.updateStatus');

    // Subscription Plans
    Route::get('/plans', [\App\Http\Controllers\Admin\PlanController::class , 'index'])->name('plans.index');
    Route::post('/plans', [\App\Http\Controllers\Admin\PlanController::class , 'store'])->name('plans.store');
    Route::put('/plans/{plan}', [\App\Http\Controllers\Admin\PlanController::class , 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [\App\Http\Controllers\Admin\PlanController::class , 'destroy'])->name('plans.destroy');
    Route::post('/plans/{plan}/force-sync', [\App\Http\Controllers\Admin\PlanController::class , 'forceSync'])->name('plans.force-sync');

    // Subscriptions
    Route::get('/subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class , 'index'])->name('subscriptions.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class , 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class , 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class , 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
