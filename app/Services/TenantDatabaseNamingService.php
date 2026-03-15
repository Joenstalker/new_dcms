<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Tenant Database Naming Service
 * 
 * Converts subdomain names into valid MySQL database names.
 * Ensures database names are safe, unique, and follow naming conventions.
 * 
 * Example transformations:
 *   "dental"        -> "dental_dcms_db"
 *   "Smile-Clinic"  -> "smile_clinic_dcms_db"
 *   "Dr. John's"    -> "dr_john_s_dcms_db"
 *   "Clinic 123"    -> "clinic_123_dcms_db"
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

    public function __construct()
    {
        $this->suffix = config('tenancy.database.suffix', '_dcms_db');
    }

    /**
     * Generate a database name from a subdomain
     * 
     * @param string $subdomain The subdomain (e.g., "dental", "smile-clinic")
     * @return string The sanitized database name (e.g., "dental_dcms_db")
     */
    public function generateDatabaseName(string $subdomain): string
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

        // Step 8: Append suffix
        $name = $name . $this->suffix;

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
