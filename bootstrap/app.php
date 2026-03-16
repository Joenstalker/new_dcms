<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::group([], base_path('routes/tenant.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'check.subscription' => \App\Http\Middleware\CheckSubscription::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if (tenant()) {
                return route('login');
            }
            
            return route('central.home'); // Or anywhere else for central guests
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException $e, \Illuminate\Http\Request $request) {
            $appUrl = config('app.url');
            $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?? 'http';
            $host = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';
            $port = parse_url($appUrl, PHP_URL_PORT) ? ':' . parse_url($appUrl, PHP_URL_PORT) : '';
            return redirect()->away("{$scheme}://{$host}{$port}/?error=clinic_not_found");
        });
    })->create();
