<?php

namespace App\Helpers;

use Illuminate\Support\Str;

/**
 * Tenant Database Helper
 * 
 * Provides hashing logic for generating secure tenant database names.
 * Format: tenant_[16char_hash_of_domain]_db
 * 
 * Example:
 *   Input: domain="dentalclinic"
 *   Hash: SHA-256 of "dentalclinic"
 *   Output: tenant_a1b2c3d4e5f6g7h8_db
 * 
 * Example:
 *   Input: domain="smile-dental"
 *   Hash: SHA-256 of "smile-dental"
 *   Output: tenant_a1b2c3d4e5f6g7h8_db
 */
class TenantDatabaseHelper
{
    /**
     * Hash length for database name (16 characters)
     */
    public const HASH_LENGTH = 16;

    /**
     * Maximum length for database name (MySQL limit is 64 characters)
     */
    public const MAX_LENGTH = 64;

    /**
     * Database name prefix
     */
    public const PREFIX = 'tenant_';

    /**
     * Database name suffix
     */
    public const SUFFIX = '_db';

    /**
     * Generate a hashed database name from domain/subdomain
     * 
     * @param string $domain The domain/subdomain (e.g., "dentalclinic", "smile-dental")
     * @return string Hashed database name (e.g., tenant_a1b2c3d4e5f6g7h8_db)
     */
    public static function generateHashedDatabaseName(string $domain): string
    {
        // Normalize the domain
        $normalizedDomain = self::normalizeDomain($domain);

        // Generate SHA-256 hash
        $hash = hash('sha256', $normalizedDomain);

        // Take first 16 characters for the hash portion
        $shortHash = substr($hash, 0, self::HASH_LENGTH);

        // Build the database name
        $databaseName = self::PREFIX . $shortHash . self::SUFFIX;

        // Validate and return
        return self::validateAndSanitize($databaseName);
    }

    /**
     * Generate a preview of the hashed database name
     * 
     * This can be used to show the user what the database name will look like
     * before actually creating the tenant.
     * 
     * @param string $domain The domain/subdomain
     * @return array Array with 'database_name', 'hash', and 'full_name' keys
     */
    public static function previewHashedDatabaseName(string $domain): array
    {
        $normalizedDomain = self::normalizeDomain($domain);
        $hash = hash('sha256', $normalizedDomain);
        $shortHash = substr($hash, 0, self::HASH_LENGTH);

        return [
            'database_name' => self::PREFIX . $shortHash . self::SUFFIX,
            'hash' => $shortHash,
            'full_hash' => $hash,
            'domain' => $normalizedDomain,
        ];
    }

    /**
     * Generate a unique database name by checking for collisions
     * 
     * @param string $domain The domain/subdomain
     * @param callable $existsCallback Function to check if database name exists
     * @return string Unique hashed database name
     */
    public static function generateUniqueHashedDatabaseName(
        string $domain,
        callable $existsCallback
        ): string
    {
        $databaseName = self::generateHashedDatabaseName($domain);

        // If no collision, return immediately
        if (!$existsCallback($databaseName)) {
            return $databaseName;
        }

        // If collision, add a counter suffix
        $counter = 1;
        $baseHash = hash('sha256', self::normalizeDomain($domain));
        $baseName = self::PREFIX . substr($baseHash, 0, 12);

        while ($existsCallback($baseName . $counter . self::SUFFIX)) {
            $counter++;

            // Prevent infinite loop
            if ($counter > 100) {
                // Fallback to UUID-based naming
                return self::PREFIX . Str::uuid()->toString() . self::SUFFIX;
            }
        }

        return $baseName . $counter . self::SUFFIX;
    }

    /**
     * Generate the database connection name
     * 
     * Format: tenant_{$tenantId}
     * 
     * @param string $tenantId Tenant ID
     * @return string Connection name
     */
    public static function generateConnectionName(string $tenantId): string
    {
        return 'tenant_' . $tenantId;
    }

    /**
     * Normalize domain for hashing
     * 
     * @param string $domain Domain to normalize
     * @return string Normalized domain
     */
    private static function normalizeDomain(string $domain): string
    {
        // Convert to lowercase
        $domain = strtolower($domain);

        // Trim whitespace
        $domain = trim($domain);

        // Remove protocol if present
        $domain = preg_replace('#^https?://#', '', $domain);

        // Remove www prefix if present
        $domain = preg_replace('#^www\.#', '', $domain);

        // Remove trailing slashes and dots
        $domain = rtrim($domain, '/.');

        return $domain;
    }

    /**
     * Validate and sanitize database name
     * 
     * Ensures the database name is safe for MySQL and within length limits.
     * 
     * @param string $databaseName Database name to validate
     * @return string Validated and sanitized database name
     */
    public static function validateAndSanitize(string $databaseName): string
    {
        // Convert to lowercase
        $name = strtolower($databaseName);

        // Replace any invalid characters with underscore
        $name = preg_replace('/[^a-z0-9_]/', '_', $name);

        // Remove multiple underscores
        $name = preg_replace('/_+/', '_', $name);

        // Trim underscores from start and end
        $name = trim($name, '_');

        // Ensure it starts with a letter
        if (!preg_match('/^[a-z]/', $name)) {
            $name = 't_' . $name;
        }

        // Truncate to max length
        $name = substr($name, 0, self::MAX_LENGTH);

        return $name;
    }

    /**
     * Validate if a database name is valid
     * 
     * @param string $name Database name to validate
     * @return bool True if valid
     */
    public static function isValidDatabaseName(string $name): bool
    {
        // Must not be empty
        if (empty($name)) {
            return false;
        }

        // Must not exceed max length
        if (strlen($name) > self::MAX_LENGTH) {
            return false;
        }

        // Must start with a letter
        if (!preg_match('/^[a-z]/', $name)) {
            return false;
        }

        // Must only contain valid characters
        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            return false;
        }

        return true;
    }

    /**
     * Extract hash from database name
     * 
     * @param string $databaseName Database name (e.g., tenant_a1b2c3d4e5f6g7h8_db)
     * @return string|null The hash portion or null if invalid format
     */
    public static function extractHashFromDatabaseName(string $databaseName): ?string
    {
        // Check if it matches the expected format
        if (!preg_match('/^tenant_([a-z0-9]+)_db$/', $databaseName, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Check if database name follows the tenant hash format
     * 
     * @param string $databaseName Database name to check
     * @return bool True if it follows the format
     */
    public static function isHashedFormat(string $databaseName): bool
    {
        return (bool)preg_match('/^tenant_[a-z0-9]{' . self::HASH_LENGTH . '}_db$/', $databaseName);
    }
}
