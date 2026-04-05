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
        'operating_hours',
        'online_booking_enabled',
        'qr_code_path',
        'storage_used_bytes',
        'bandwidth_used_bytes',
        'version',
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
        'operating_hours' => 'json',
        'online_booking_enabled' => 'boolean',
        'storage_used_bytes' => 'integer',
        'bandwidth_used_bytes' => 'integer',
        'version' => 'string',
    ];

    /**
     * Check if the tenant can use advanced branding customizations
     */
    public function canCustomizeBranding(): bool
    {
        return $this->hasPlanFeature('custom_branding');
    }

    /**
     * Check if online booking is enabled for this tenant
     */
    public function isOnlineBookingEnabled(): bool
    {
        return $this->online_booking_enabled ?? true;
    }

    /**
     * Get operating hours with sensible defaults
     */
    public function getOperatingHoursWithDefaults(): array
    {
        $defaults = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            $defaults[$day] = [
                'enabled' => !in_array($day, ['saturday', 'sunday']),
                'open' => '08:00',
                'close' => '17:00',
            ];
        }

        $hours = $this->operating_hours;

        // Handle cases where the value might be a JSON string from the database/Inertia
        if (is_string($hours) && !empty($hours)) {
            try {
                $decoded = json_decode($hours, true);
                if (is_array($decoded)) {
                    $hours = $decoded;
                }
            }
            catch (\Exception $e) {
            // Fallback to defaults on parse error
            }
        }

        return is_array($hours) ? $hours : $defaults;
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

                // If no domain, use the tenant ID (subdomain) as source, then name as fallback
                if (!$domain) {
                    $domain = $tenant->id ?? $tenant->name;
                }

                $hashedDbName = $namingService->generateHashedDatabaseName($domain);
                $tenant->database_name = $hashedDbName;

                // Set the stancl internal key so DatabaseConfig::getName() uses the hashed name
                // for physical database creation instead of falling back to prefix+id+suffix
                $tenant->setInternal('db_name', $hashedDbName);
            }

            // Generate database connection name
            if (empty($tenant->database_connection)) {
                $tenant->database_connection = TenantDatabaseHelper::generateConnectionName($tenant->id);
            }

            // Set default status
            if (empty($tenant->status)) {
                $tenant->status = self::STATUS_ACTIVE;
            }

            // Set default enabled features
            if (empty($tenant->enabled_features)) {
                $tenant->enabled_features = self::getDefaultFeatures();
            }

            // Set default initial system version
            if (empty($tenant->version)) {
                $tenant->version = config('app_version.version', '1.0.0');
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
            'analytics',
            'notifications',
            'logs',
            'branches',
            'settings',
            'branding'
        ];
    }

    /**
     * Check if a feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool
    {
        $enabled = $this->enabled_features;

        if (is_string($enabled) && !empty($enabled)) {
            $decoded = json_decode($enabled, true);
            if (is_array($decoded)) {
                $enabled = $decoded;
            }
        }

        if (!is_array($enabled)) {
            $enabled = self::getDefaultFeatures();
        }

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
     * Check if the tenant can add more patients based on their plan limits.
     */
    public function canAddMorePatients(): bool
    {
        $limit = $this->getPlanLimit('max_patients');
        if ($limit === null || $limit === -1)
            return true; // Unlimited

        $currentCount = \DB::connection($this->getDatabaseConnectionName())
            ->table('patients')
            ->count();

        return $currentCount < $limit;
    }

    /**
     * Check if the tenant can add more users (staff) based on their plan limits.
     */
    public function canAddMoreUsers(): bool
    {
        $limit = $this->getPlanLimit('max_users');
        if ($limit === null || $limit === -1)
            return true; // Unlimited

        $currentCount = \DB::connection($this->getDatabaseConnectionName())
            ->table('users')
            ->count();

        return $currentCount < $limit;
    }

    /**
     * Check if the tenant can add more appointments based on their plan limits.
     */
    public function canAddMoreAppointments(): bool
    {
        $limit = $this->getPlanLimit('max_appointments');
        if ($limit === null || $limit === -1)
            return true; // Unlimited

        $currentCount = \DB::connection($this->getDatabaseConnectionName())
            ->table('appointments')
            ->count();

        return $currentCount < $limit;
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

    /**
     * Get the tenant feature overrides.
     */
    public function tenantFeatures()
    {
        return $this->hasMany(TenantFeature::class);
    }
}
