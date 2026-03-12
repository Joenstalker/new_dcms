<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\ProfileController;
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

// Admin Routes
Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Tenant Management
    Route::get('/tenants', [\App\Http\Controllers\Admin\TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'show'])->name('tenants.show');
    Route::put('/tenants/{tenant}/status', [\App\Http\Controllers\Admin\TenantController::class, 'updateStatus'])->name('tenants.updateStatus');

    // Subscription Plans
    Route::resource('plans', \App\Http\Controllers\Admin\PlanController::class);

    // Subscriptions
    Route::get('/subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('subscriptions.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
