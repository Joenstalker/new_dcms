<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemEarning extends Model
{
    protected $fillable = [
        'tenant_id',
        'amount',
        'currency',
        'stripe_payment_intent_id',
        'stripe_session_id',
        'description',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];
}
