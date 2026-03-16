<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use App\Services\TenantDatabaseNamingService;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'name',
        'status',
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

        // Use domain-based ID instead of UUID
        static::creating(function ($tenant) {
            if (empty($tenant->id)) {
                $namingService = app(TenantDatabaseNamingService::class);
                $tenant->id = $namingService->generateDatabaseName($tenant->name);
            }
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

// Remove getCustomColumns so all extra fields map to the 'data' JSON column.
}
