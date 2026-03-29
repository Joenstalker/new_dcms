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
            if (!Schema::hasTable('branding_settings')) return $default;

            if (array_key_exists($key, self::$cache)) {
                return self::$cache[$key];
            }

            $row = DB::table('branding_settings')->where('key', $key)->first();
            if (!$row) return $default;

            // Priority 1: Binary Value (Return a route URL instead of base64 to avoid memory spikes)
            if ($row->binary_value !== null) {
                // Return the route URL that streams this binary data
                $value = route('settings.logo', ['key' => $key]);
                self::$cache[$key] = $value;
                return $value;
            }

            // Priority 2: Stored Value (JSON or String)
            $value = self::castValue($row->value);
            self::$cache[$key] = $value;

            return $value;
        } catch (\Exception $e) {
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
            if (!file_exists($filePath)) return;

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
            
            fclose($fp);
            
            // Clear cache for this key
            unset(self::$cache[$key]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("TenantBrandingService setStreamed($key) Error: " . $e->getMessage());
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

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("TenantBrandingService set($key) Error: " . $e->getMessage());
        }
    }

    /**
     * Get multiple settings at once (Optimized).
     */
    public static function findMany(array $keys): array
    {
        try {
            if (!Schema::hasTable('branding_settings')) return [];

            // Identify missing keys from cache
            $missingKeys = array_diff($keys, array_keys(self::$cache));
            
            if (!empty($missingKeys)) {
                $rows = DB::table('branding_settings')->whereIn('key', $missingKeys)->get();
                foreach ($rows as $row) {
                    // Priority 1: Binary value — return route URL
                    if ($row->binary_value !== null) {
                        self::$cache[$row->key] = route('settings.logo', ['key' => $row->key]);
                    } else {
                        self::$cache[$row->key] = self::castValue($row->value);
                    }
                }
            }

            return array_intersect_key(self::$cache, array_flip($keys));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all settings (Simplified).
     */
    public static function getAll(): array
    {
        try {
            if (!Schema::hasTable('branding_settings')) return [];

            $rows = DB::table('branding_settings')->get();
            $settings = [];
            foreach ($rows as $row) {
                // Priority 1: Binary value — return route URL
                if ($row->binary_value !== null) {
                    $settings[$row->key] = route('settings.logo', ['key' => $row->key]);
                } else {
                    $settings[$row->key] = self::castValue($row->value);
                }
                self::$cache[$row->key] = $settings[$row->key];
            }
            return $settings;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Helper to cast values from the DB.
     */
    private static function castValue($value)
    {
        if ($value === null) return null;
        if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return $value;
    }
}
