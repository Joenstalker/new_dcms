<?php

namespace App\Models;

use App\Helpers\TenantDatabaseHelper;
use App\Services\TenantDatabaseNamingService;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\DatabaseConfig;

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
        'db_used_bytes',
        'bandwidth_used_bytes',
        'last_db_measured_at',
        'last_storage_reconciled_at',
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
        'db_used_bytes' => 'integer',
        'bandwidth_used_bytes' => 'integer',
        'last_db_measured_at' => 'datetime',
        'last_storage_reconciled_at' => 'datetime',
        'version' => 'string',
    ];

    /**
     * Define the custom columns that shouldn't be serialized into the data JSON blob.
     */
    public static function getCustomColumns(): array
    {
        return [
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
            'database',
            'database_connection',
            'logo_path',
            'logo_login_path',
            'logo_booking_path',
            'font_family',
            'enabled_features',
            'landing_page_config',
            'operating_hours',
            'online_booking_enabled',
            'storage_used_bytes',
            'db_used_bytes',
            'bandwidth_used_bytes',
            'last_db_measured_at',
            'last_storage_reconciled_at',
            'version',
        ];
    }

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
                'enabled' => ! in_array($day, ['saturday', 'sunday']),
                'open' => '08:00',
                'close' => '17:00',
            ];
        }

        $hours = $this->operating_hours;

        // Handle cases where the value might be a JSON string from the database/Inertia
        if (is_string($hours) && ! empty($hours)) {
            try {
                $decoded = json_decode($hours, true);
                if (is_array($decoded)) {
                    $hours = $decoded;
                }
            } catch (\Exception $e) {
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

        static::creating(function ($tenant) {
            // Generate unique tenant ID using UUID (safer for identification)
            if (empty($tenant->id)) {
                $tenant->id = (string) Str::uuid();
            }

            if (empty($tenant->database_name)) {
                $domain = null;

                if ($tenant->relationLoaded('domains') && $tenant->domains->isNotEmpty()) {
                    $domain = $tenant->domains->first()->domain ?? null;
                }

                if (! $domain) {
                    $domain = $tenant->id ?? $tenant->name;
                }

                if (config('tenancy.use_hashed_database_names', true)) {
                    $namingService = app(TenantDatabaseNamingService::class);
                    $hashedDbName = $namingService->generateHashedDatabaseName((string) $domain);
                    $tenant->database_name = $hashedDbName;
                    $tenant->setInternal('db_name', $hashedDbName);
                } else {
                    $prefix = config('tenancy.database.prefix', 'tenant_');
                    $suffix = config('tenancy.database.suffix', '_db');
                    $dbName = $prefix.$tenant->getTenantKey().$suffix;
                    $tenant->database_name = $dbName;
                    $tenant->setInternal('db_name', $dbName);
                }
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

            // Ensure public landing page config is always complete (avoids errors before first branding save)
            $tenant->landing_page_config = self::mergeLandingPageConfig(
                is_array($tenant->landing_page_config) ? $tenant->landing_page_config : null
            );
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
        return $this->database ?? $this->database_name ?? config('tenancy.database.prefix').$this->id.config('tenancy.database.suffix');
    }

    /**
     * Eloquent accessor that guarantees the `database_name` property serialized to the frontend
     * perfectly matches the securely hashed backend database.
     */
    public function getDatabaseNameAttribute($value): string
    {
        return $value ?: $this->getInternal('db_name');
    }

    /**
     * Override Stancl Tenancy's internal configuration to map securely to our hashed database
     */
    public function getInternal(string $key)
    {
        $value = parent::getInternal($key);

        if ($key === 'db_name' && empty($value)) {
            $dbName = $this->attributes['database'] ?? $this->attributes['database_name'] ?? null;
            if (empty($dbName)) {
                $domain = $this->id ?? $this->name;

                if ($this->relationLoaded('domains') && $this->domains->isNotEmpty()) {
                    $domain = $this->domains->first()->domain ?? $domain;
                }

                if (config('tenancy.use_hashed_database_names', true)) {
                    $namingService = app(TenantDatabaseNamingService::class);
                    $dbName = $namingService->generateHashedDatabaseName((string) $domain);
                } else {
                    $prefix = config('tenancy.database.prefix', 'tenant_');
                    $suffix = config('tenancy.database.suffix', '_db');
                    $dbName = $prefix.$this->getTenantKey().$suffix;
                }
            }

            return $dbName;
        }

        return $value;
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
        return ! empty($this->database_name) && TenantDatabaseHelper::isHashedFormat($this->database_name);
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
            'medical_records',
            'staff',
            'services',
            'reports',
            'analytics',
            'notifications',
            'logs',
            'branches',
            'settings',
            'branding',
        ];
    }

    /**
     * Baseline landing page JSON for new tenants and for merging with partial stored config.
     */
    public static function defaultLandingPageConfig(): array
    {
        return [
            'background_color' => '#ffffff',
            'text_primary' => '#111827',
            'text_secondary' => '#4b5563',
            'team' => [
                'source_mode' => 'auto_staff',
                'include_owner' => true,
                'manual_cards' => [],
            ],
        ];
    }

    /**
     * Merge stored landing config with defaults so required keys (e.g. team.source_mode) always exist.
     *
     * @param  array<string, mixed>|null  $existing
     * @return array<string, mixed>
     */
    public static function mergeLandingPageConfig(?array $existing): array
    {
        $defaults = self::defaultLandingPageConfig();
        $existing = is_array($existing) ? $existing : [];

        $teamExisting = isset($existing['team']) && is_array($existing['team']) ? $existing['team'] : [];
        $withoutTeam = $existing;
        unset($withoutTeam['team']);

        $merged = array_replace_recursive($defaults, $withoutTeam);
        $merged['team'] = array_merge($defaults['team'], $teamExisting);

        $mode = $merged['team']['source_mode'] ?? 'auto_staff';
        if (! in_array($mode, ['auto_staff', 'manual', 'hybrid'], true)) {
            $merged['team']['source_mode'] = 'auto_staff';
        }

        if (! isset($merged['team']['manual_cards']) || ! is_array($merged['team']['manual_cards'])) {
            $merged['team']['manual_cards'] = [];
        }

        $merged['team']['include_owner'] = (bool) ($merged['team']['include_owner'] ?? true);

        return $merged;
    }

    /**
     * Public website URL for a tenant subdomain (matches tenancy host routing).
     */
    public static function publicWebsiteUrlForSubdomain(string $subdomain): string
    {
        $appUrl = config('app.url', 'http://localhost');
        $parsed = parse_url($appUrl) ?: [];
        $scheme = ($parsed['scheme'] ?? 'http') === 'https' ? 'https' : 'http';
        $host = $parsed['host'] ?? '';
        if ($host === '') {
            $host = preg_replace('#^https?://#i', '', (string) $appUrl);
            $host = explode('/', $host, 2)[0];
            $host = explode(':', $host, 2)[0];
        }
        if ($host === '') {
            $host = 'localhost';
        }
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

        // Avoid duplicating subdomain if already present (e.g., junjun-smile.localhost)
        $hostParts = explode('.', $host);
        if (strtolower($hostParts[0]) === strtolower($subdomain)) {
            $finalHost = $host;
        } else {
            $finalHost = $subdomain . '.' . $host;
        }

        return $scheme . '://' . $finalHost . $port;
    }

    /**
     * Check if a feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return in_array($feature, $this->getResolvedEnabledFeaturesForUi(), true);
    }

    /**
     * Resolve tenant UI module visibility from branding overrides or tenant defaults.
     * If custom branding is not entitled by plan, only return baseline defaults.
     */
    public function getResolvedEnabledFeaturesForUi(?array $brandingOverrides = null): array
    {
        if (! $this->canCustomizeBranding()) {
            return self::getDefaultFeatures();
        }

        $enabled = $brandingOverrides['enabled_features'] ?? $this->enabled_features ?? self::getDefaultFeatures();

        if (is_string($enabled) && $enabled !== '') {
            $decoded = json_decode($enabled, true);
            if (is_array($decoded)) {
                $enabled = $decoded;
            }
        }

        if (! is_array($enabled)) {
            return self::getDefaultFeatures();
        }

        return array_values(array_unique($enabled));
    }

    /**
     * Check if the tenant's subscription plan has a specific feature enabled.
     */
    public function hasPlanFeature(string $key): bool
    {
        $subscription = $this->subscription;
        if (! $subscription || ! $subscription->plan) {
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
        if (! $subscription || ! $subscription->plan) {
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
        if ($limit === null || $limit === -1) {
            return true;
        } // Unlimited

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
        if ($limit === null || $limit === -1) {
            return true;
        } // Unlimited

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
        if ($limit === null || $limit === -1) {
            return true;
        } // Unlimited

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
