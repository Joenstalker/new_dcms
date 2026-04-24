<?php

namespace App\Providers;

use App\Helpers\TenantDatabaseHelper;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

/**
 * Tenant Database Service Provider
 * 
 * Handles dynamic database connections for tenant databases.
 * Each tenant gets its own database connection that can be
 * switched to on-the-fly.
 */
class TenantDatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register singleton for managing tenant connections
        $this->app->singleton(TenantConnectionManager::class , function ($app) {
            return new TenantConnectionManager();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    //
    }
}

/**
 * Tenant Connection Manager
 * 
 * Manages dynamic database connections for tenants.
 * Allows switching between tenant databases programmatically.
 */
class TenantConnectionManager
{
    /**
     * Registered connections cache
     */
    protected array $connections = [];

    /**
     * Current active tenant connection
     */
    protected ?string $currentConnection = null;

    /**
     * Get or create a connection for a tenant
     * 
     * @param Tenant $tenant The tenant to connect to
     * @return string The connection name
     */
    public function connect(Tenant $tenant): string
    {
        $connectionName = $tenant->getDatabaseConnectionName();

        // Check if connection already exists
        if (isset($this->connections[$connectionName])) {
            $this->currentConnection = $connectionName;
            return $connectionName;
        }

        // Create the dynamic connection
        $this->createConnection($tenant);

        $this->currentConnection = $connectionName;

        return $connectionName;
    }

    /**
     * Create a dynamic database connection for a tenant
     * 
     * @param Tenant $tenant The tenant
     * @return void
     */
    protected function createConnection(Tenant $tenant): void
    {
        $connectionName = $tenant->getDatabaseConnectionName();
        $databaseName = $tenant->getDatabaseName();

        // Get the template connection configuration
        $templateConnection = config('database.connections.mysql');

        // Create the new connection configuration
        $newConnection = array_merge($templateConnection, [
            'driver' => 'mysql',
            'host' => config('database.connections.central.host', '127.0.0.1'),
            'port' => config('database.connections.central.port', '3306'),
            'database' => $databaseName,
            'username' => config('database.connections.central.username', 'root'),
            'password' => config('database.connections.central.password', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        // Add the connection to the database config
        config(["database.connections.{$connectionName}" => $newConnection]);

        // Cache the connection name
        $this->connections[$connectionName] = true;
    }

    /**
     * Switch to a tenant's database
     * 
     * @param Tenant $tenant The tenant to switch to
     * @return void
     */
    public function switchToTenant(Tenant $tenant): void
    {
        $connectionName = $this->connect($tenant);
        config(['database.default' => $connectionName]);
        DB::setDefaultConnection($connectionName);
    }

    /**
     * Switch back to the central database
     * 
     * @return void
     */
    public function switchToCentral(): void
    {
        config(['database.default' => 'central']);
        DB::setDefaultConnection('central');
        $this->currentConnection = 'central';
    }

    /**
     * Get the current connection name
     * 
     * @return string|null
     */
    public function getCurrentConnection(): ?string
    {
        return $this->currentConnection;
    }

    /**
     * Check if connected to a specific tenant
     * 
     * @param Tenant $tenant The tenant to check
     * @return bool
     */
    public function isConnectedTo(Tenant $tenant): bool
    {
        return $this->currentConnection === $tenant->getDatabaseConnectionName();
    }

    /**
     * Create the tenant database if it doesn't exist
     * 
     * @param Tenant $tenant The tenant
     * @return bool True if created or already exists
     */
    public function createDatabaseIfNotExists(Tenant $tenant): bool
    {
        $databaseName = $tenant->getDatabaseName();

        try {
            // Use central connection to create database
            DB::connection('central')->statement(
                "CREATE DATABASE IF NOT EXISTS `{$databaseName}`"
            );
            return true;
        }
        catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Drop the tenant database
     * 
     * @param Tenant $tenant The tenant
     * @return bool True if dropped successfully
     */
    public function dropDatabase(Tenant $tenant): bool
    {
        $databaseName = $tenant->getDatabaseName();

        try {
            // Use central connection to drop database
            DB::connection('central')->statement(
                "DROP DATABASE IF EXISTS `{$databaseName}`"
            );

            // Remove the connection from cache
            $connectionName = $tenant->getDatabaseConnectionName();
            unset($this->connections[$connectionName]);

            return true;
        }
        catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Check if a database name already exists
     * 
     * @param string $databaseName The database name to check
     * @return bool True if exists
     */
    public function databaseExists(string $databaseName): bool
    {
        try {
            $result = DB::connection('central')
                ->select('SHOW DATABASES LIKE ?', [$databaseName]);

            return count($result) > 0;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all tenant databases
     * 
     * @return array Array of database names
     */
    public function getAllTenantDatabases(): array
    {
        $prefix = config('tenancy.database.prefix', 'tenant_');

        try {
            $databases = DB::connection('central')
                ->select('SHOW DATABASES');

            return array_filter(array_map(function ($db) use ($prefix) {
                $dbName = $db->Database ?? $db->name ?? null;
                if ($dbName && str_starts_with($dbName, $prefix)) {
                    return $dbName;
                }
                return null;
            }, $databases));
        }
        catch (\Exception $e) {
            return [];
        }
    }
}
