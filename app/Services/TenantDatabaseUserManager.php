<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Tenant Database User Manager
 * 
 * Manages MySQL database users for tenant isolation.
 * Each tenant gets a dedicated database user with restricted permissions.
 * 
 * Security features:
 * - Unique username per tenant
 * - Random secure password
 * - Limited to specific database only
 * - No access to other databases or system tables
 */
class TenantDatabaseUserManager
{
    /**
     * Create a database user for a tenant
     * 
     * @param string $databaseName The tenant database name
     * @param string $username Optional custom username
     * @return array [username, password]
     */
    public function createDatabaseUser(string $databaseName, ?string $username = null): array
    {
        $username = $username ?? $this->generateUsername($databaseName);
        $password = $this->generatePassword();

        try {
            // Create the MySQL user using string interpolation for DDL
            DB::statement("CREATE USER '{$username}'@'%' IDENTIFIED BY '{$password}'");

            // Grant privileges to the specific database only
            DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON `{$databaseName}`.* TO '{$username}'@'%'");


            // Apply changes
            DB::statement("FLUSH PRIVILEGES");

            Log::info('Tenant database user created', [
                'username' => $username,
                'database' => $databaseName
            ]);

            return [
                'username' => $username,
                'password' => $password
            ];
        }
        catch (\Exception $e) {
            Log::error('Failed to create tenant database user', [
                'username' => $username,
                'database' => $databaseName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Drop a database user for a tenant
     * 
     * @param string $username
     * @return bool
     */
    public function dropDatabaseUser(string $username): bool
    {
        try {
            DB::statement("DROP USER IF EXISTS '{$username}'@'%'");
            DB::statement("FLUSH PRIVILEGES");

            Log::info('Tenant database user dropped', [
                'username' => $username
            ]);

            return true;
        }
        catch (\Exception $e) {
            Log::error('Failed to drop tenant database user', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update password for a database user
     * 
     * @param string $username
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword(string $username, string $newPassword): bool
    {
        try {
            DB::statement("ALTER USER '{$username}'@'%' IDENTIFIED BY '{$newPassword}'");
            DB::statement("FLUSH PRIVILEGES");

            Log::info('Tenant database user password updated', [
                'username' => $username
            ]);

            return true;
        }
        catch (\Exception $e) {
            Log::error('Failed to update tenant database user password', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if a database user exists
     * 
     * @param string $username
     * @return bool
     */
    public function userExists(string $username): bool
    {
        try {
            $result = DB::select("SELECT 1 FROM mysql.user WHERE User = ?", [$username]);
            return !empty($result);
        }
        catch (\Exception $e) {
            Log::error('Failed to check if database user exists', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate a unique username for the tenant
     * 
     * @param string $databaseName
     * @return string
     */
    protected function generateUsername(string $databaseName): string
    {
        $prefix = config('tenancy.database.user_prefix', 'tenant_user_');

        // Remove suffix from database name
        $suffix = config('tenancy.database.suffix', '_db');
        $baseName = str_replace($suffix, '', $databaseName);

        // Sanitize for username
        $baseName = preg_replace('/[^a-z0-9_]/i', '', $baseName);

        // Truncate if needed (MySQL username limit is 32 chars)
        $maxLength = 32 - strlen($prefix);
        $baseName = substr($baseName, 0, max(0, $maxLength));

        return $prefix . $baseName;
    }

    /**
     * Generate a secure random password
     * 
     * @param int $length
     * @return string
     */
    protected function generatePassword(int $length = 20): string
    {
        return Str::random($length);
    }

    /**
     * Get grants for a database user
     * 
     * @param string $username
     * @return array
     */
    public function getGrants(string $username): array
    {
        try {
            return DB::select("SHOW GRANTS FOR '{$username}'@'%'");
        }
        catch (\Exception $e) {
            Log::error('Failed to get database user grants', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
