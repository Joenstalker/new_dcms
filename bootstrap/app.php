<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
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
            \App\Http\Middleware\EnsurePasswordIsChanged::class,
            \App\Http\Middleware\TrackTenantRequests::class,
            \App\Http\Middleware\TrackTenantBandwidth::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'registration/webhook',
            'api/login',
            'api/login/google',
            'api/password/code/send',
            'api/password/code/verify',
            'api/password/code/reset',
            'api/password/email',
            'api/password/reset',
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'check.subscription' => \App\Http\Middleware\CheckSubscription::class,
            'tenant.feature' => \App\Http\Middleware\CheckTenantFeature::class,
            'tenant.limit' => \App\Http\Middleware\CheckTenantLimit::class,
            'tenant.preview.impersonate' => \App\Http\Middleware\ImpersonateTenantPreviewUser::class,
            'tenant.init.preview_or_subdomain' => \App\Http\Middleware\InitializeTenancyBySubdomainOrPreview::class,
            'tenant.prevent.central_or_preview' => \App\Http\Middleware\PreventAccessFromCentralDomainsOrPreview::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if (tenant()) {
                return route('tenant.landing');
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

        $exceptions->reportable(function (\Spatie\Permission\Exceptions\UnauthorizedException $e) {
            if (tenant() && auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('activity_log')) {
                activity()
                    ->causedBy(auth()->user())
                    ->event('unauthorized_access')
                    ->log('User attempted to access an unauthorized route or action.');
            }
        });

        $exceptions->reportable(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
            if (tenant() && auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('activity_log')) {
                activity()
                    ->causedBy(auth()->user())
                    ->event('unauthorized_access')
                    ->log('User attempted to access an unauthorized resource.');
            }
        });
    })->create();
