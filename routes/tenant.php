<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    \App\Http\Middleware\CheckTenantStatus::class,
])->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    // Public QR Booking Route
    Route::get('/book', [\App\Http\Controllers\BookingController::class, 'create'])->name('tenant.book.create');
    Route::post('/book', [\App\Http\Controllers\BookingController::class, 'store'])->name('tenant.book.store');

    require __DIR__.'/auth.php';

    // Authenticated Tenant Routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
        
        Route::resource('patients', \App\Http\Controllers\PatientController::class);
        Route::resource('appointments', \App\Http\Controllers\AppointmentController::class)->only(['index', 'store', 'update']);
        Route::resource('treatments', \App\Http\Controllers\TreatmentController::class)->only(['index', 'store']);
        Route::resource('billing', \App\Http\Controllers\BillingController::class)->only(['index', 'store', 'update']);

        // Owner only routes
        Route::middleware(['role:Owner'])->group(function () {
            Route::get('staff/schedules', [\App\Http\Controllers\StaffController::class, 'schedules'])->name('staff.schedules');
            Route::resource('staff', \App\Http\Controllers\StaffController::class);
            Route::put('staff/{staff}/permissions', [\App\Http\Controllers\StaffController::class, 'updatePermissions'])->name('staff.update-permissions');
            Route::get('reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
            Route::get('settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
            Route::post('settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
        });

        // Expanded Access Routes
        Route::middleware(['role:Owner|Dentist|Assistant'])->group(function () {
            Route::resource('services', \App\Http\Controllers\ServiceController::class);
        });

        Route::middleware(['role:Owner|Dentist'])->group(function () {
            Route::post('services/{service}/approve', [\App\Http\Controllers\ServiceController::class, 'approve'])->name('services.approve');
            Route::post('services/{service}/reject', [\App\Http\Controllers\ServiceController::class, 'reject'])->name('services.reject');
        });
    });
});
