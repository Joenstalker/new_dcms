<?php

namespace App\Services;

use App\Helpers\TenantDatabaseHelper;
use Illuminate\Support\Str;

/**
 * Tenant Database Naming Service
 * 
 * Converts subdomain names into valid MySQL database names.
 * Supports both domain-based naming and hash-based naming.
 * 
 * Domain-based format:
 *   "dental"        -> "dental" (tenant ID)
 *   "Smile-Clinic"  -> "smile_clinic"
 * 
 * Hash-based format:
 *   "dentalclinic" -> "tenant_a1b2c3d4e5f6g7h8_db" (SHA-256 hash of domain)
 *
 * Final DB name (domain-based): tenant_{id}_db (e.g., tenant_dental_db)
 * Final DB name (hash-based): tenant_{hash}_db (e.g., tenant_a1b2c3d4e5f6g7h8_db)
 */
class TenantDatabaseNamingService
{
    /**
     * The suffix to append to database names
     */
    protected string $suffix;

    /**
     * Maximum length for database name (MySQL limit is 64 characters)
     */
    protected const MAX_LENGTH = 64;

    /**
     * Characters that are invalid in MySQL database names
     */
    protected const INVALID_CHARS = '/[^a-z0-9_]/i';

    /**
     * Whether to use hash-based naming
     */
    protected bool $useHashedNaming;

    public function __construct()
    {
        $this->suffix = config('tenancy.database.suffix', '_db');
        $this->useHashedNaming = config('tenancy.use_hashed_database_names', true);
    }

    /**
     * Generate a database name from a subdomain
     * 
     * @param string $subdomain The subdomain (e.g., "dental", "smile-clinic")
     * @return string The sanitized tenant ID (e.g., "dental") or hashed name
     */
    public function generateDatabaseName(string $subdomain): string
    {
        if ($this->useHashedNaming) {
            return $this->generateHashedDatabaseName($subdomain);
        }

        return $this->generateDomainBasedDatabaseName($subdomain);
    }

    /**
     * Generate a hash-based database name from domain
     * 
     * @param string $domain The domain/subdomain (e.g., "dentalclinic")
     * @return string Hashed database name (e.g., tenant_a1b2c3d4e5f6g7h8_db)
     */
    public function generateHashedDatabaseName(string $domain): string
    {
        return TenantDatabaseHelper::generateHashedDatabaseName($domain);
    }

    /**
     * Preview hashed database name (for UI display before creation)
     * 
     * @param string $domain The domain/subdomain
     * @return array Preview data
     */
    public function previewHashedDatabaseName(string $domain): array
    {
        return TenantDatabaseHelper::previewHashedDatabaseName($domain);
    }

    /**
     * Generate a domain-based database name (original method)
     * 
     * @param string $subdomain The subdomain (e.g., "dental", "smile-clinic")
     * @return string The sanitized tenant ID (e.g., "dental")
     */
    public function generateDomainBasedDatabaseName(string $subdomain): string
    {
        // Step 1: Convert to lowercase
        $name = strtolower($subdomain);

        // Step 2: Trim whitespace
        $name = trim($name);

        // Step 3: Replace spaces and hyphens with underscores
        $name = str_replace([' ', '-'], '_', $name);

        // Step 4: Remove special characters (keep only alphanumeric and underscore)
        $name = preg_replace(self::INVALID_CHARS, '', $name);

        // Step 5: Remove multiple underscores
        $name = preg_replace('/_+/', '_', $name);

        // Step 6: Remove leading/trailing underscores
        $name = trim($name, '_');

        // Step 7: Ensure it starts with a letter (MySQL requirement)
        if (!preg_match('/^[a-z]/', $name)) {
            $name = 'tenant_' . $name;
        }

        // Step 8: Suffix is applied by Stancl Tenancy via config

        // Step 9: Truncate to max length
        $name = substr($name, 0, self::MAX_LENGTH);

        return $name;
    }

    /**
     * Generate a database username for the tenant
     * 
     * @param string $subdomain The subdomain
     * @return string The database username
     */
    public function generateDatabaseUsername(string $subdomain): string
    {
        $prefix = config('tenancy.database.user_prefix', 'tenant_user_');
        $name = $this->generateDatabaseName($subdomain);

        // Remove the suffix for username
        $name = str_replace($this->suffix, '', $name);

        // Truncate if needed (MySQL username limit is 32 chars)
        $maxUsernameLength = 32;
        $username = $prefix . $name;

        if (strlen($username) > $maxUsernameLength) {
            $username = substr($username, 0, $maxUsernameLength);
        }

        return $username;
    }

    /**
     * Check if a database name is valid
     * 
     * @param string $name
     * @return bool
     */
    public function isValidDatabaseName(string $name): bool
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
     * Make a database name unique by appending a number if needed
     * 
     * @param string $baseName The desired database name
     * @param callable $existsCallback Function to check if name exists
     * @return string Unique database name
     */
    public function makeUnique(string $baseName, callable $existsCallback): string
    {
        $name = $baseName;
        $counter = 1;

        while ($existsCallback($name)) {
            $name = $baseName . '_' . $counter;
            $counter++;
        }

        return $name;
    }
}
