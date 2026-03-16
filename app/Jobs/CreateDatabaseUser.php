<?php

namespace App\Jobs;

use App\Services\TenantDatabaseUserManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\Tenant;


/**
 * Create Database User Job
 * 
 * Creates a dedicated MySQL user for a tenant database.
 * This provides an additional layer of security by ensuring
 * each tenant has their own database credentials.
 */
class CreateDatabaseUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * Execute the job.
     */
    public function handle(TenantDatabaseUserManager $userManager): void
    {
        $tenant = tenancy()->tenant;

        if (!$tenant) {
            Log::error('CreateDatabaseUser: No tenant context found');
            return;
        }

        $databaseName = $tenant->getTenantKey();

        try {
            // Check if user already exists
            $username = $this->generateUsername($databaseName);

            if ($userManager->userExists($username)) {
                Log::info('CreateDatabaseUser: User already exists, skipping', [
                    'username' => $username,
                    'tenant_id' => $databaseName
                ]);
                return;
            }

            // Create the database user
            $credentials = $userManager->createDatabaseUser($databaseName, $username);

            // Store encrypted credentials in tenant data
            $tenant->update([
                'data' => array_merge($tenant->data ?? [], [
                    'db_username' => $credentials['username'],
                    'db_password_encrypted' => Crypt::encryptString($credentials['password']),
                ])
            ]);

            Log::info('CreateDatabaseUser: Database user created successfully', [
                'username' => $username,
                'tenant_id' => $databaseName
            ]);
        }
        catch (\Exception $e) {
            Log::error('CreateDatabaseUser: Failed to create database user', [
                'tenant_id' => $databaseName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate username from database name
     */
    protected function generateUsername(string $databaseName): string
    {
        $prefix = config('tenancy.database.user_prefix', 'tenant_user_');
        $suffix = config('tenancy.database.suffix', '_db');

        $baseName = str_replace($suffix, '', $databaseName);
        $baseName = preg_replace('/[^a-z0-9_]/i', '', $baseName);

        $maxLength = 32 - strlen($prefix);
        $baseName = substr($baseName, 0, max(0, $maxLength));

        return $prefix . $baseName;
    }
}
