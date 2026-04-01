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
        Vite::prefetch(concurrency: 3);

        \Illuminate\Support\Facades\Event::listen(function (\Illuminate\Auth\Events\Login $event) {
            if (tenant() && Schema::hasTable('activity_log')) {
                activity()
                    ->causedBy($event->user)
                    ->event('login')
                    ->log('User logged in');
            }
        });

        \Illuminate\Support\Facades\Event::listen(function (\Illuminate\Auth\Events\Failed $event) {
            if (tenant() && Schema::hasTable('activity_log')) {
                $userStr = $event->user ? 'User ' . $event->user->email : 'Unknown user (' . json_encode($event->credentials) . ')';
                activity()
                    ->causedBy($event->user)
                    ->event('failed_login')
                    ->log("$userStr failed to log in");
            }
        });
    }
}
