<?php

namespace App\Database\IdGenerators;

use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Contracts\IdGenerator;

/**
 * Domain-Based Tenant ID Generator
 * 
 * Generates tenant IDs based on the domain/subdomain name instead of UUID.
 * This allows for human-readable tenant IDs that match database naming.
 * 
 * Example:
 *   Input: "Dental Clinic" -> Output: "dental_clinic_dcms_db"
 *   Input: "Smile"         -> Output: "smile_dcms_db"
 */
class DomainBasedIdGenerator implements IdGenerator
{
    /**
     * Generate a tenant ID from tenant data
     * 
     * @param Tenant $tenant The tenant instance
     * @return string The generated ID
     */
    public function generate(Tenant $tenant): string
    {
        // Get the name from tenant attributes
        $name = $tenant->name ?? $tenant->getAttribute('name') ?? '';

        if (empty($name)) {
            throw new \RuntimeException('Tenant name is required for ID generation');
        }

        // Sanitize the name to create a valid database name
        return $this->sanitizeName($name);
    }

    /**
     * Sanitize a name to create a valid tenant ID
     * 
     * @param string $name
     * @return string
     */
    protected function sanitizeName(string $name): string
    {
        // Convert to lowercase
        $name = strtolower($name);

        // Trim whitespace
        $name = trim($name);

        // Replace spaces and hyphens with underscores
        $name = str_replace([' ', '-'], '_', $name);

        // Remove special characters (keep only alphanumeric and underscore)
        $name = preg_replace('/[^a-z0-9_]/i', '', $name);

        // Remove multiple underscores
        $name = preg_replace('/_+/', '_', $name);

        // Remove leading/trailing underscores
        $name = trim($name, '_');

        // Ensure it starts with a letter
        if (!preg_match('/^[a-z]/', $name)) {
            $name = 'tenant_' . $name;
        }

        // Get suffix from config
        $suffix = config('tenancy.database.suffix', '_dcms_db');

        // Append suffix
        $name = $name . $suffix;

        // Truncate to max length (MySQL limit is 64 characters)
        $name = substr($name, 0, 64);

        return $name;
    }
}
