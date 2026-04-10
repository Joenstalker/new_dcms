<?php

namespace App\Providers;

use App\Models\SupportTicket;
use App\Models\User;
use App\Policies\SupportTicketPolicy;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function () {
            return new StripeClient(config('services.stripe.secret'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureLocalhostSessionCookieDomain();
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);

        Vite::prefetch(concurrency: 3);

        Event::listen(function (Login $event) {
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

        Event::listen(function (Failed $event) {
            if (tenant() && Schema::hasTable('activity_log')) {
                $attemptedEmail = strtolower((string) ($event->credentials['email'] ?? ''));
                $matchedUser = $attemptedEmail !== ''
                    ? User::query()->where('email', $attemptedEmail)->select(['id', 'email'])->first()
                    : null;

                $knownStatus = $matchedUser ? 'known staff email' : 'unknown email';
                $description = $attemptedEmail !== ''
                    ? "Failed login attempt for {$attemptedEmail} ({$knownStatus})"
                    : 'Failed login attempt with missing email';

                activity()
                    ->causedBy($event->user)
                    ->withProperties([
                        'attempted_email' => $attemptedEmail ?: null,
                        'email_exists' => (bool) $matchedUser,
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
