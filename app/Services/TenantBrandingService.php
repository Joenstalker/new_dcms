<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class TenantBrandingService
{
    private static $cache = [];

    /**
     * Get a branding setting from the tenant database.
     */
    public static function get(string $key, $default = null)
    {
        try {
            if (!Schema::hasTable('branding_settings'))
                return $default;

            $key = str_replace(' ', '_', $key);

            if (array_key_exists($key, self::$cache)) {
                return self::$cache[$key];
            }

            $row = DB::table('branding_settings')
                ->where('key', $key)
                ->selectRaw('`value`, `type`, `updated_at`, CASE WHEN `binary_value` IS NOT NULL THEN 1 ELSE 0 END as has_binary')
                ->first();
            if (!$row)
                return $default;

            // Priority 1: Binary Value (Return a route URL instead of base64 to avoid memory spikes)
            if ($row->has_binary) {
                // Return the route URL that streams this binary data with cache buster
                $timestamp = $row->updated_at ? strtotime($row->updated_at) : time();
                $value = route('settings.logo', ['key' => $key]) . '?v=' . $timestamp;
                self::$cache[$key] = $value;
                return $value;
            }

            // Priority 2: Stored Value (JSON or String)
            $value = self::castValue($row->value);
            self::$cache[$key] = $value;

            return $value;
        }
        catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Set a branding setting in the tenant database using a stream.
     * Use this for images to avoid memory exhaustion!
     */
    public static function setStreamed(string $key, string $filePath)
    {
        try {
            if (!file_exists($filePath))
                return;

            $fp = fopen($filePath, 'rb');
            if ($fp === false) {
                \Illuminate\Support\Facades\Log::error("TenantBrandingService setStreamed($key) Error: Unable to open file: $filePath");
                return;
            }

            // Use RAW PDO for LOB streaming support
            $pdo = DB::getPdo();
            $stmt = $pdo->prepare("INSERT INTO branding_settings (`key`, `binary_value`, `type`, `updated_at`, `created_at`) 
                                    VALUES (?, ?, 'binary', NOW(), NOW()) 
                                    ON DUPLICATE KEY UPDATE `binary_value` = VALUES(`binary_value`), `updated_at` = NOW()");

            $stmt->bindParam(1, $key);
            $stmt->bindParam(2, $fp, \PDO::PARAM_LOB);
            $stmt->execute();

        }
        catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("TenantBrandingService setStreamed($key) Error: " . $e->getMessage());
        }
        finally {
            if (isset($fp) && is_resource($fp)) {
                fclose($fp);
            }

            // Clear cache for this key
            self::$cache[$key] = null;
            unset(self::$cache[$key]);
        }
    }

    /**
     * Set a branding setting in the tenant database.
     */
    public static function set(string $key, $value)
    {
        try {
            DB::table('branding_settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => (is_array($value) || is_object($value)) ? json_encode($value) : (string)$value,
                'type' => (is_array($value) || is_object($value)) ? 'json' : 'string',
                'binary_value' => null, // Clear binary if setting a normal value
                'updated_at' => now(),
                'created_at' => now(),
            ]
            );

            // Update local cache
            self::$cache[$key] = $value;

        }
        catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("TenantBrandingService set($key) Error: " . $e->getMessage());
        }
    }

    /**
     * Remove a branding setting from the local cache.
     */
    public static function forget(string $key): void
    {
        unset(self::$cache[$key]);
    }

    /**
     * Get multiple settings at once (Optimized).
     */
    public static function findMany(array $keys): array
    {
        try {
            if (!Schema::hasTable('branding_settings'))
                return [];

            // Identify missing keys from cache
            $missingKeys = array_diff($keys, array_keys(self::$cache));

            if (!empty($missingKeys)) {
                $rows = DB::table('branding_settings')
                    ->whereIn('key', $missingKeys)
                    ->selectRaw('`key`, `value`, `type`, `updated_at`, CASE WHEN `binary_value` IS NOT NULL THEN 1 ELSE 0 END as has_binary')
                    ->get();
                foreach ($rows as $row) {
                    // Priority 1: Binary value — return route URL
                    if ($row->has_binary) {
                        $timestamp = $row->updated_at ? strtotime($row->updated_at) : time();
                        self::$cache[$row->key] = route('settings.logo', ['key' => $row->key]) . '?v=' . $timestamp;
                    }
                    else {
                        self::$cache[$row->key] = self::castValue($row->value);
                    }
                }
            }

            return array_intersect_key(self::$cache, array_flip($keys));
        }
        catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all settings (Simplified).
     */
    public static function getAll(): array
    {
        try {
            if (!Schema::hasTable('branding_settings'))
                return [];

            $rows = DB::table('branding_settings')
                ->selectRaw('`key`, `value`, `type`, `updated_at`, CASE WHEN `binary_value` IS NOT NULL THEN 1 ELSE 0 END as has_binary')
                ->get();
            $settings = [];
            foreach ($rows as $row) {
                // Normalize key: replace spaces with underscores to prevent URL encoding issues
                $normalizedKey = str_replace(' ', '_', $row->key);

                // Priority 1: Binary value — return route URL
                if ($row->has_binary) {
                    $timestamp = $row->updated_at ? strtotime($row->updated_at) : time();
                    $settings[$normalizedKey] = route('settings.logo', ['key' => $row->key]) . '?v=' . $timestamp;
                }
                else {
                    $settings[$normalizedKey] = self::castValue($row->value);
                }

                // Also cache with normalized key
                self::$cache[$normalizedKey] = $settings[$normalizedKey];

                // If it was modified, also keep original for backward compatibility (if needed)
                if ($normalizedKey !== $row->key) {
                    self::$cache[$row->key] = $settings[$normalizedKey];
                }
            }
            return $settings;
        }
        catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Helper to cast values from the DB.
     */
    private static function castValue($value)
    {
        if ($value === null)
            return null;
        if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return $value;
    }
}
