<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\DatabaseConfig;
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
        'logo_login_path',
        'logo_booking_path',
        'font_family',
        'enabled_features',
        'landing_page_config',
        'qr_code_path',
        'storage_used_bytes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => 'string',
        'enabled_features' => 'json',
        'landing_page_config' => 'json',
        'font_family' => 'json',
        'portal_config' => 'json',
        'storage_used_bytes' => 'integer',
    ];

    /**
     * Check if the tenant can use advanced branding customizations
     */
    public function canCustomizeBranding(): bool
    {
        // Gating: Only Pro and Ultimate plans can customize branding
        // If no subscription, assume trial/basic (safety first)
        $sub = $this->subscription ?? null;
        if (!$sub || !$sub->plan) return false;
        
        return in_array($sub->plan->name, ['Pro', 'Ultimate']);
    }

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
                $tenant->database = $tenant->database_name; // Set standard Stancl key
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
     * Get the fallback database name for this tenant.
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

    /**
     * Get the default enabled features for a new tenant
     */
    public static function getDefaultFeatures(): array
    {
        return [
            'dashboard',
            'appointments',
            'patients',
            'billing',
            'treatments',
            'staff',
            'services',
            'reports',
            'settings'
        ];
    }

    /**
     * Check if a feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool
    {
        $enabled = $this->enabled_features ?? self::getDefaultFeatures();
        return in_array($feature, $enabled);
    }

    /**
     * Check if the tenant's subscription plan has a specific feature enabled.
     */
    public function hasPlanFeature(string $key): bool
    {
        $subscription = $this->subscription;
        if (!$subscription || !$subscription->plan) {
            return false;
        }
        return $subscription->plan->hasFeature($key);
    }

    /**
     * Get a numeric limit from the tenant's subscription plan.
     */
    public function getPlanLimit(string $key): mixed
    {
        $subscription = $this->subscription;
        if (!$subscription || !$subscription->plan) {
            return null;
        }
        return $subscription->plan->getFeatureValue($key);
    }

    /**
     * Check if the tenant can be safely deleted by an admin.
     * Prevents deletion of active paying tenants.
     */
    public function canBeDeleted(): bool
    {
        // If they have an active subscription, they cannot be deleted.
        $activeSubscription = $this->subscriptions()
            ->where('stripe_status', 'active')
            ->exists();

        if ($activeSubscription) {
            return false;
        }

        // Additional guard: cannot delete if status is 'active'
        if ($this->status === self::STATUS_ACTIVE) {
            return false;
        }

        return true;
    }
}
