<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use App\Services\TenantDatabaseNamingService;
use App\Helpers\TenantDatabaseHelper;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'name',
        'owner_name',
        'email',
        'phone',
        'street',
        'region',
        'barangay',
        'city',
        'province',
        'status',
        'domain_id',
        'database_name',
        'database_connection',
        'branding_color',
        'hero_title',
        'hero_subtitle',
        'about_us_description',
        'logo_path',
        'qr_code_path',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Default status for new tenants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Use hash-based database name from domain
        static::creating(function ($tenant) {
            $namingService = app(TenantDatabaseNamingService::class);

            // Generate unique tenant ID using UUID (safer for identification)
            if (empty($tenant->id)) {
                $tenant->id = (string)\Illuminate\Support\Str::uuid();
            }

            // Generate hashed database name from domain
            // Get the first domain if available, otherwise use the tenant name
            if (empty($tenant->database_name)) {
                $domain = null;

                // Try to get domain from the tenant's domains relationship
                if ($tenant->relationLoaded('domains') && $tenant->domains->isNotEmpty()) {
                    $domain = $tenant->domains->first()->domain ?? null;
                }

                // If no domain, use the tenant name as fallback
                if (!$domain) {
                    $domain = $tenant->name;
                }

                $tenant->database_name = $namingService->generateHashedDatabaseName($domain);
            }

            // Generate database connection name
            if (empty($tenant->database_connection)) {
                $tenant->database_connection = TenantDatabaseHelper::generateConnectionName($tenant->id);
            }

            // Set default status
            if (empty($tenant->status)) {
                $tenant->status = self::STATUS_ACTIVE;
            }
        });
    }

    /**
     * Get the sanitized subdomain from tenant ID
     */
    public function getSubdomainAttribute(): string
    {
        $suffix = config('tenancy.database.suffix', '_db');
        return str_replace($suffix, '', $this->id);
    }

    /**
     * Get subscriptions for this tenant
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the active subscription
     */
    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if tenant is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Get the database name for this tenant
     */
    public function getDatabaseName(): string
    {
        return $this->database_name ?? config('tenancy.database.prefix') . $this->id . config('tenancy.database.suffix');
    }

    /**
     * Get the database connection name for this tenant
     */
    public function getDatabaseConnectionName(): string
    {
        return $this->database_connection ?? TenantDatabaseHelper::generateConnectionName($this->id);
    }

    /**
     * Check if this tenant uses hashed database naming
     */
    public function usesHashedDatabaseName(): bool
    {
        return !empty($this->database_name) && TenantDatabaseHelper::isHashedFormat($this->database_name);
    }

// Remove getCustomColumns so all extra fields map to the 'data' JSON column.
}
