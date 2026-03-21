<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
     * Get the subscription plan
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class , 'subscription_plan_id');
    }

    /**
     * Get the tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get days remaining until billing cycle ends
     */
    public function getDaysRemainingAttribute(): int
    {
        $endDate = $this->billing_cycle_end ?? $this->ends_at;

        if (!$endDate) {
            // Calculate based on billing cycle
            $days = $this->billing_cycle === 'yearly' ? 365 : 30;
            $endDate = $this->created_at->addDays($days);
        }

        return max(0, (int)ceil(Carbon::now()->diffInHours($endDate, false) / 24));
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->stripe_status === 'active' && $this->payment_status === 'paid';
    }

    /**
     * Check if subscription is past due
     */
    public function isPastDue(): bool
    {
        return in_array($this->stripe_status, ['past_due', 'unpaid']);
    }

    /**
     * Check if subscription is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->stripe_status === 'canceled';
    }

    /**
     * Toggle payment method status
     */
    public function togglePaymentMethod(string $method): bool
    {
        $field = $method . '_enabled';

        if (!in_array($field, ['stripe_enabled', 'gcash_enabled', 'paymaya_enabled', 'bank_transfer_enabled'])) {
            return false;
        }

        return $this->update([$field => !$this->$field]);
    }

    /**
     * Enable a payment method
     */
    public function enablePaymentMethod(string $method): bool
    {
        $field = $method . '_enabled';

        if (!in_array($field, ['stripe_enabled', 'gcash_enabled', 'paymaya_enabled', 'bank_transfer_enabled'])) {
            return false;
        }

        return $this->update([$field => true]);
    }

    /**
     * Disable a payment method
     */
    public function disablePaymentMethod(string $method): bool
    {
        $field = $method . '_enabled';

        if (!in_array($field, ['stripe_enabled', 'gcash_enabled', 'paymaya_enabled', 'bank_transfer_enabled'])) {
            return false;
        }

        return $this->update([$field => false]);
    }

    /**
     * Extend billing cycle
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
     * Get all enabled payment methods
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
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('stripe_status', 'active')
            ->where('payment_status', 'paid');
    }

    /**
     * Scope for past due subscriptions
     */
    public function scopePastDue($query)
    {
        return $query->whereIn('stripe_status', ['past_due', 'unpaid']);
    }

    /**
     * Scope for cancelled subscriptions
     */
    public function scopeCancelled($query)
    {
        return $query->where('stripe_status', 'canceled');
    }
}
