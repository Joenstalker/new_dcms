<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsageOverage extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'metric',
        'billing_period_start',
        'billing_period_end',
        'included_bytes',
        'used_bytes',
        'overage_bytes',
        'billable_quantity_gb',
        'unit_price_per_gb',
        'amount',
        'currency',
        'stripe_invoice_item_id',
        'stripe_invoice_id',
        'stripe_event_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'billing_period_start' => 'datetime',
        'billing_period_end' => 'datetime',
        'included_bytes' => 'integer',
        'used_bytes' => 'integer',
        'overage_bytes' => 'integer',
        'billable_quantity_gb' => 'decimal:4',
        'unit_price_per_gb' => 'decimal:2',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function getConnectionName()
    {
        if (app()->runningUnitTests()) {
            return config('database.default');
        }

        return parent::getConnectionName();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
