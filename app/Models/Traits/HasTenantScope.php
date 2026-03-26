<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Tenant Scope Trait
 * 
 * Automatically scopes queries to the current tenant.
 * Use this trait on any model that should be tenant-specific.
 */
trait HasTenantScope
{
    /**
     * Get the current tenant ID.
     */
    protected static function getCurrentTenantId()
    {
        try {
            $tenant = tenancy()->tenant;
            return $tenant ? $tenant->id : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Boot the trait.
     */
    protected static function bootHasTenantScope(): void
    {
        static::creating(function ($model) {
            // Automatically set tenant_id when creating
            if (empty($model->tenant_id)) {
                $tenantId = static::getCurrentTenantId();

                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });

        // Add global scope to automatically filter by tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = static::getCurrentTenantId();

            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Get the tenant ID column name.
     */
    public function getTenantKeyName(): string
    {
        return 'tenant_id';
    }

    /**
     * Get the tenant ID.
     */
    public function getTenantKey(): ?string
    {
        return $this->getAttribute('tenant_id');
    }

    /**
     * Check if the model belongs to the current tenant.
     */
    public function belongsToCurrentTenant(): bool
    {
        $currentTenantId = static::getCurrentTenantId();

        return $this->tenant_id === $currentTenantId;
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeTenant(Builder $query, ?string $tenantId = null): Builder
    {
        $tenantId = $tenantId ?? static::getCurrentTenantId();

        if ($tenantId) {
            return $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    /**
     * Scope to get all records regardless of tenant (for admin).
     */
    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
