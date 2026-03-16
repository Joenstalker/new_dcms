<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feature extends Model
{
    protected $connection = 'central';
    protected $fillable = [
        'key',
        'name',
        'description',
        'type',
        'category',
        'options',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the subscription plans that have this feature.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class , 'plan_features')
            ->withPivot('value_boolean', 'value_numeric', 'value_tier')
            ->withTimestamps();
    }

    /**
     * Get the value for a specific plan.
     */
    public function getValueForPlan(SubscriptionPlan $plan): mixed
    {
        $pivot = $this->plans()->where('subscription_plan_id', $plan->id)->first();

        if (!$pivot) {
            return null;
        }

        return match ($this->type) {
                'boolean' => $pivot->pivot->value_boolean,
                'numeric' => $pivot->pivot->value_numeric,
                'tiered' => $pivot->pivot->value_tier,
                default => null,
            };
    }

    /**
     * Check if this feature is enabled for a specific plan.
     */
    public function isEnabledForPlan(SubscriptionPlan $plan): bool
    {
        $value = $this->getValueForPlan($plan);

        return match ($this->type) {
                'boolean' => $value === true,
                'numeric' => $value !== null,
                'tiered' => $value !== null,
                default => false,
            };
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter active features.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the display name for the feature type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
                'boolean' => 'Yes/No',
                'numeric' => 'Number',
                'tiered' => 'Tiered',
                default => 'Unknown',
            };
    }

    /**
     * Get the display name for the category.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
                'core' => 'Core Features',
                'limits' => 'Limits',
                'addons' => 'Add-ons',
                'reports' => 'Reports & Analytics',
                'expansion' => 'Expansion',
                default => 'Other',
            };
    }
}
