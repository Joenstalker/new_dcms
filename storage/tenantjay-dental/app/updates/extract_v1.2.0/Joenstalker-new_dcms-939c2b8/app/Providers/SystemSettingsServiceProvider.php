<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SystemSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Don't run during migrations or if the table doesn't exist yet
        if ($this->app->runningInConsole() && !str_contains(request()->fullUrl() ?? '', 'admin')) {
            // We want it to run in console for certain commands, but let's be safe
        }

        try {
            if (Schema::hasTable('system_settings')) {
                $this->overrideConfigurations();
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the app during early boot/migrations
        }
    }

    /**
     * Override Laravel configurations with database settings.
     */
    protected function overrideConfigurations(): void
    {
        // Cache settings for 1 hour to avoid DB pressure
        $settings = Cache::remember('system_settings_all', 3600, function () {
            return SystemSetting::all()->pluck('value', 'key');
        });

        if ($settings->isEmpty()) {
            return;
        }

        // Apply Session Settings
        if (isset($settings['session_lifetime'])) {
            config(['session.lifetime' => (int) $settings['session_lifetime']]);
        }

        if (isset($settings['session_expire_on_close'])) {
            config(['session.expire_on_close' => filter_var($settings['session_expire_on_close'], FILTER_VALIDATE_BOOLEAN)]);
        }

        if (isset($settings['session_encrypt'])) {
            config(['session.encrypt' => filter_var($settings['session_encrypt'], FILTER_VALIDATE_BOOLEAN)]);
        }

        // Apply Password Reset Expiry
        if (isset($settings['password_reset_expiry'])) {
            config(['auth.passwords.users.expire' => (int) $settings['password_reset_expiry']]);
        }

        // Apply Remember Me Duration (This affects the cookie lifetime)
        if (isset($settings['remember_me_duration'])) {
            // Laravel doesn't have a direct config for this, it's often hardcoded in the guard
            // but we can set a custom config for other parts of the app to use
            config(['auth.remember_me_duration' => (int) $settings['remember_me_duration']]);
        }
    }
}
