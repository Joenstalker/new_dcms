<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $tenant_id
 * @property int $subscription_plan_id
 * @property string|null $stripe_id
 * @property string|null $stripe_status
 * @property float|null $stripe_price
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $ends_at
 * @property string|null $billing_cycle
 * @property string|null $payment_method
 * @property string|null $payment_status
 * @property Carbon|null $billing_cycle_end
 * @property-read SubscriptionPlan|null $plan
 * @property-read Tenant|null $tenant
 */
class Subscription extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'trial_ends_at',
        'ends_at',
        'billing_cycle',
        'payment_method',
        'payment_status',
        // Payment method toggles
        'stripe_enabled',
        'gcash_enabled',
        'paymaya_enabled',
        'bank_transfer_enabled',
        // Billing cycle management
        'billing_cycle_end',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'billing_cycle_end' => 'datetime',
        'stripe_enabled' => 'boolean',
        'gcash_enabled' => 'boolean',
        'paymaya_enabled' => 'boolean',
        'bank_transfer_enabled' => 'boolean',
    ];

    /**
     * Get the subscription plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Get the tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function paymentHistories(): HasMany
    {
        return $this->hasMany(PaymentHistory::class);
    }

    public function usageOverages(): HasMany
    {
        return $this->hasMany(SubscriptionUsageOverage::class);
    }

    public function latestPaymentHistory(): HasOne
    {
        return $this->hasOne(PaymentHistory::class)->latestOfMany('paid_at');
    }

    /**
     * Get days remaining until billing cycle ends.
     */
    public function getDaysRemainingAttribute(): int
    {
        $endDate = $this->billing_cycle_end ?? $this->ends_at;

        if (! $endDate) {
            // Calculate based on billing cycle.
            $days = $this->billing_cycle === 'yearly' ? 365 : 30;
            $endDate = $this->created_at->addDays($days);
        }

        return max(0, (int) ceil(Carbon::now()->diffInHours($endDate, false) / 24));
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->stripe_status === 'active' && $this->payment_status === 'paid';
    }

    /**
     * Check if subscription is past due.
     */
    public function isPastDue(): bool
    {
        return in_array($this->stripe_status, ['past_due', 'unpaid']);
    }

    /**
     * Check if subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->stripe_status === 'canceled';
    }

    /**
     * Toggle payment method status.
     */
    public function togglePaymentMethod(string $method): bool
    {
        $field = $method.'_enabled';

        if (! in_array($field, ['stripe_enabled', 'gcash_enabled', 'paymaya_enabled', 'bank_transfer_enabled'])) {
            return false;
        }

        return $this->update([$field => ! $this->$field]);
    }

    /**
     * Enable a payment method.
     */
    public function enablePaymentMethod(string $method): bool
    {
        $field = $method.'_enabled';

        if (! in_array($field, ['stripe_enabled', 'gcash_enabled', 'paymaya_enabled', 'bank_transfer_enabled'])) {
            return false;
        }

        return $this->update([$field => true]);
    }

    /**
     * Disable a payment method.
     */
    public function disablePaymentMethod(string $method): bool
    {
        $field = $method.'_enabled';

        if (! in_array($field, ['stripe_enabled', 'gcash_enabled', 'paymaya_enabled', 'bank_transfer_enabled'])) {
            return false;
        }

        return $this->update([$field => false]);
    }

    /**
     * Extend billing cycle.
     */
    public function extendBillingCycle(int $days): bool
    {
        $currentEnd = $this->billing_cycle_end ?? $this->ends_at ?? now();
        $newEnd = $currentEnd->addDays($days);

        return $this->update([
            'billing_cycle_end' => $newEnd,
            'ends_at' => $newEnd,
        ]);
    }

    /**
     * Get all enabled payment methods.
     *
     * @return array<int, string>
     */
    public function getEnabledPaymentMethodsAttribute(): array
    {
        $methods = [];

        if ($this->stripe_enabled) {
            $methods[] = 'stripe';
        }
        if ($this->gcash_enabled) {
            $methods[] = 'gcash';
        }
        if ($this->paymaya_enabled) {
            $methods[] = 'paymaya';
        }
        if ($this->bank_transfer_enabled) {
            $methods[] = 'bank_transfer';
        }

        return $methods;
    }

    /**
     * Scope for active subscriptions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('stripe_status', 'active')
            ->where('payment_status', 'paid');
    }

    /**
     * Scope for past due subscriptions.
     */
    public function scopePastDue(Builder $query): Builder
    {
        return $query->whereIn('stripe_status', ['past_due', 'unpaid']);
    }

    /**
     * Scope for cancelled subscriptions.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('stripe_status', 'canceled');
    }
}
