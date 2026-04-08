<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\Stripe\StripeClient::class, function () {
            return new \Stripe\StripeClient(config('services.stripe.secret'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureLocalhostSessionCookieDomain();

        Vite::prefetch(concurrency: 3);

        \Illuminate\Support\Facades\Event::listen(function (\Illuminate\Auth\Events\Login $event) {
            if (tenant() && Schema::hasTable('activity_log')) {
                activity()
                    ->causedBy($event->user)
                    ->withProperties([
                        'email' => $event->user?->email,
                        'ip' => request()?->ip(),
                    ])
                    ->event('login')
                    ->log('User logged in');
            }
        });

        \Illuminate\Support\Facades\Event::listen(function (\Illuminate\Auth\Events\Failed $event) {
            if (tenant() && Schema::hasTable('activity_log')) {
                $attemptedEmail = strtolower((string)($event->credentials['email'] ?? ''));
                $matchedUser = $attemptedEmail !== ''
                    ? \App\Models\User::query()->where('email', $attemptedEmail)->select(['id', 'email'])->first()
                    : null;

                $knownStatus = $matchedUser ? 'known staff email' : 'unknown email';
                $description = $attemptedEmail !== ''
                    ? "Failed login attempt for {$attemptedEmail} ({$knownStatus})"
                    : 'Failed login attempt with missing email';

                activity()
                    ->causedBy($event->user)
                    ->withProperties([
                        'attempted_email' => $attemptedEmail ?: null,
                        'email_exists' => (bool)$matchedUser,
                        'matched_user_id' => $matchedUser?->id,
                        'ip' => request()?->ip(),
                    ])
                    ->event('failed_login')
                    ->log($description);
            }
        });
    }

    /**
     * Browsers treat localhost cookie domains specially.
     * For *.localhost tenant subdomains, force host-only cookies so auth sessions persist.
     */
    private function configureLocalhostSessionCookieDomain(): void
    {
        try {
            $host = request()->getHost();

            if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
                config([
                    'session.domain' => null,
                    'session.secure' => false,
                    'session.same_site' => 'lax',
                ]);
            }
        } catch (\Throwable $e) {
            // Ignore host resolution issues during CLI/bootstrap edge cases.
        }
    }
}
