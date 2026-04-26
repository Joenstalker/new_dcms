<?php

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CheckTenantFeature;
use App\Http\Middleware\CheckTenantLimit;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureTenantSessionIsolation;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ImpersonateTenantPreviewUser;
use App\Http\Middleware\InitializeTenancyBySubdomainOrPreview;
use App\Http\Middleware\InitializeTenancyForBroadcastingAuth;
use App\Http\Middleware\PreventAccessFromCentralDomainsOrPreview;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetTenantUrl;
use App\Http\Middleware\TrackTenantBandwidth;
use App\Http\Middleware\TrackTenantRequests;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        [
            'middleware' => [
                'web',
                InitializeTenancyForBroadcastingAuth::class,
                EnsureTenantSessionIsolation::class,
            ],
        ]
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            EnsurePasswordIsChanged::class,
            TrackTenantRequests::class,
            TrackTenantBandwidth::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'registration/webhook',
            'registration/check-email',
            'registration/check-subdomain',
            'registration/validate-account',
            'registration/validate-subdomain',
            'registration/suggest-subdomain',
            'registration/checkout',
            'registration/complete',
            'api/login',
            'api/login/google',
            'api/password/code/send',
            'api/password/code/verify',
            'api/password/code/reset',
            'api/password/email',
            'api/password/reset',
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'check.subscription' => CheckSubscription::class,
            'tenant.feature' => CheckTenantFeature::class,
            'tenant.limit' => CheckTenantLimit::class,
            'tenant.preview.impersonate' => ImpersonateTenantPreviewUser::class,
            'tenant.session.isolated' => EnsureTenantSessionIsolation::class,
            'tenant.init.preview_or_subdomain' => InitializeTenancyBySubdomainOrPreview::class,
            'tenant.prevent.central_or_preview' => PreventAccessFromCentralDomainsOrPreview::class,
        ]);

        $middleware->priority([
            InitializeTenancyBySubdomainOrPreview::class,
            InitializeTenancyForBroadcastingAuth::class,
            SetTenantUrl::class,
            PreventAccessFromCentralDomainsOrPreview::class,
            ImpersonateTenantPreviewUser::class,
            Authenticate::class,
            EnsureTenantSessionIsolation::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if (tenant()) {
                return route('tenant.landing');
            }

            return route('central.home'); // Or anywhere else for central guests
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TenantCouldNotBeIdentifiedOnDomainException $e, Request $request) {
            $appUrl = config('app.url');
            $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?? 'http';
            $host = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';
            $port = parse_url($appUrl, PHP_URL_PORT) ? ':'.parse_url($appUrl, PHP_URL_PORT) : '';

            return redirect()->away("{$scheme}://{$host}{$port}/?error=clinic_not_found");
        });

        $exceptions->reportable(function (UnauthorizedException $e) {
            if (tenant() && auth()->check() && Schema::hasTable('activity_log')) {
                activity()
                    ->causedBy(auth()->user())
                    ->event('unauthorized_access')
                    ->log('User attempted to access an unauthorized route or action.');
            }
        });

        $exceptions->reportable(function (AccessDeniedHttpException $e) {
            if (tenant() && auth()->check() && Schema::hasTable('activity_log')) {
                activity()
                    ->causedBy(auth()->user())
                    ->event('unauthorized_access')
                    ->log('User attempted to access an unauthorized resource.');
            }
        });
    })->create();
