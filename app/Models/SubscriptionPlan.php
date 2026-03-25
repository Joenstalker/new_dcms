<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubscriptionPlan extends Model
{
    protected $connection = 'central';
    protected $loadedFeatures = null;

    protected $fillable = [
        'name',
        'stripe_product_id',
        'stripe_monthly_price_id',
        'stripe_yearly_price_id',
        'legacy_stripe_id',
        'price_monthly',
        'price_yearly',
        'yearly_discount_percent',
        'max_users',
        'max_patients',
        'max_appointments',
        'has_qr_booking',
        'has_sms',
        'has_branding',
        'has_analytics',
        'has_priority_support',
        'has_multi_branch',
        'report_level',
        'max_storage_mb',
    ];

    /** @deprecated Use dynamic features pivot instead */
    public $legacy_max_users;
    /** @deprecated Use dynamic features pivot instead */
    public $legacy_max_patients;
    /** @deprecated Use dynamic features pivot instead */
    public $legacy_max_appointments;
    /** @deprecated Use dynamic features pivot instead */
    public $legacy_has_qr_booking;
    /** @deprecated Use dynamic features pivot instead */
    public $legacy_has_sms;
    /** @deprecated Use dynamic features pivot instead */
    public $legacy_has_branding;
    /** @deprecated Use dynamic features pivot instead */
    public $legacy_has_analytics;
    /** @deprecated Use dynamic features pivot instead */
    public $legacy_has_priority_support;
    /** @deprecated Use dynamic features pivot instead */
    public $legacy_has_multi_branch;
    /** @deprecated Use dynamic features pivot instead */
    public $legacy_report_level;

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'yearly_discount_percent' => 'float',
        'max_users' => 'integer',
        'max_patients' => 'integer',
        'max_appointments' => 'integer',
        'has_qr_booking' => 'boolean',
        'has_sms' => 'boolean',
        'has_branding' => 'boolean',
        'has_analytics' => 'boolean',
        'has_priority_support' => 'boolean',
        'has_multi_branch' => 'boolean',
        'max_storage_mb' => 'integer',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get all features associated with this plan.
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class , 'plan_features')
            ->withPivot('value_boolean', 'value_numeric', 'value_tier', 'pushed_at')
            ->withTimestamps();
    }

    /**
     * Get all features for this plan, cached on the instance to avoid redundant queries.
     */
    public function getLoadedFeatures()
    {
        if ($this->loadedFeatures === null) {
            $this->loadedFeatures = $this->features()->get();
        }
        return $this->loadedFeatures;
    }

    /**
     * Get feature by key (dynamic approach).
     */
    public function getFeature(string $key): ?Feature
    {
        return $this->getLoadedFeatures()->firstWhere('key', $key);
    }

    /**
     * Check if the plan has a specific feature enabled.
     * Maps legacy 'has_*' keys to dynamic feature keys.
     */
    public function hasFeature(string $key): bool
    {
        // Internal mapping for transition
        $mapping = [
            'has_qr_booking' => 'qr_booking',
            'has_sms' => 'sms_notifications',
            'has_branding' => 'custom_branding',
            'has_analytics' => 'advanced_analytics',
            'has_priority_support' => 'priority_support',
            'has_multi_branch' => 'multi_branch',
        ];

        $lookupKey = $mapping[$key] ?? $key;
        
        $feature = $this->getFeature($lookupKey);
        if ($feature) {
            return $feature->isEnabledForPlan($this);
        }

        // Final fallback for purely hardcoded columns (like Stripe IDs) that aren't features
        return isset($this->{$key}) ? (bool)$this->{$key} : false;
    }

    /**
     * Get the value of a specific feature.
     * Maps legacy keys to dynamic feature keys.
     */
    public function getFeatureValue(string $key): mixed
    {
        $mapping = [
            'has_qr_booking' => 'qr_booking',
            'has_sms' => 'sms_notifications',
            'has_branding' => 'custom_branding',
            'has_analytics' => 'advanced_analytics',
            'has_priority_support' => 'priority_support',
            'has_multi_branch' => 'multi_branch',
            'max_users' => 'max_users',
            'max_patients' => 'max_patients',
            'max_appointments' => 'max_appointments',
        ];

        $lookupKey = $mapping[$key] ?? $key;

        $feature = $this->getFeature($lookupKey);
        if ($feature) {
            return $feature->getValueForPlan($this);
        }

        return $this->{$key} ?? null;
    }

    /**
     * Get all features grouped by category.
     */
    public function getFeaturesByCategory(): array
    {
        $features = $this->features()->ordered()->get();
        $grouped = [];

        foreach ($features as $feature) {
            $category = $feature->category ?? 'other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = [
                'id' => $feature->id,
                'key' => $feature->key,
                'name' => $feature->name,
                'description' => $feature->description,
                'type' => $feature->type,
                'value' => $feature->getValueForPlan($this),
                'is_enabled' => $feature->isEnabledForPlan($this),
            ];
        }

        return $grouped;
    }

    /**
     * Sync features from the old columns to the new dynamic system.
     * This is useful for migrating existing data.
     */
    public function syncFeaturesFromLegacy(): void
    {
        $featureMappings = [
            'has_qr_booking' => 'qr_booking',
            'has_sms' => 'sms_notifications',
            'has_branding' => 'custom_branding',
            'has_analytics' => 'advanced_analytics',
            'has_priority_support' => 'priority_support',
            'has_multi_branch' => 'multi_branch',
            'max_users' => 'max_users',
            'max_patients' => 'max_patients',
            'max_appointments' => 'max_appointments',
            'report_level' => 'report_level',
        ];

        foreach ($featureMappings as $oldColumn => $featureKey) {
            $feature = Feature::where('key', $featureKey)->first();
            if (!$feature) {
                continue;
            }

            $value = match ($feature->type) {
                    'boolean' => $this->{ $oldColumn},
                    'numeric' => $this->{ $oldColumn},
                    'tiered' => $this->{ $oldColumn},
                    default => null,
                };

            $this->features()->syncWithoutDetaching([
                $feature->id => match ($feature->type) {
                    'boolean' => ['value_boolean' => $value],
                    'numeric' => ['value_numeric' => $value],
                    'tiered' => ['value_tier' => $value],
                    default => [],
                },
            ]);
        }
    }

    /**
     * Add a feature to this plan with a specific value.
     */
    public function addFeature(Feature $feature, mixed $value): void
    {
        $pivotData = match ($feature->type) {
                'boolean' => ['value_boolean' => (bool)$value],
                'numeric' => ['value_numeric' => (int)$value],
                'tiered' => ['value_tier' => $value],
                default => [],
            };

        $this->features()->syncWithoutDetaching([
            $feature->id => $pivotData,
        ]);
    }

    /**
     * Remove a feature from this plan.
     */
    public function removeFeature(Feature $feature): void
    {
        $this->features()->detach($feature->id);
    }
}
