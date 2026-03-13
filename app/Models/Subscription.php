<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
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
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class , 'subscription_plan_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
