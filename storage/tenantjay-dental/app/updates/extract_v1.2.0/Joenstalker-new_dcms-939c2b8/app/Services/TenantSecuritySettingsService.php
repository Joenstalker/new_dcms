<?php

namespace App\Services;

class TenantSecuritySettingsService
{
    public const KEY_LOGIN_MAX_ATTEMPTS = 'login_max_attempts';
    public const KEY_LOGIN_LOCKOUT_MINUTES = 'login_lockout_minutes';

    /**
     * Get tenant max failed attempts before lockout.
     */
    public static function getLoginMaxAttempts(int $default = 5): int
    {
        $value = (int) TenantBrandingService::get(self::KEY_LOGIN_MAX_ATTEMPTS, $default);

        return max(1, min($value, 100));
    }

    /**
     * Get tenant lockout duration in minutes.
     */
    public static function getLoginLockoutMinutes(int $default = 15): int
    {
        $value = (int) TenantBrandingService::get(self::KEY_LOGIN_LOCKOUT_MINUTES, $default);

        return max(1, min($value, 1440));
    }

    /**
     * Persist tenant max attempts.
     */
    public static function setLoginMaxAttempts(int $attempts): void
    {
        TenantBrandingService::set(self::KEY_LOGIN_MAX_ATTEMPTS, max(1, min($attempts, 100)));
    }

    /**
     * Persist tenant lockout duration.
     */
    public static function setLoginLockoutMinutes(int $minutes): void
    {
        TenantBrandingService::set(self::KEY_LOGIN_LOCKOUT_MINUTES, max(1, min($minutes, 1440)));
    }
}
