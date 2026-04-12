<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemEarning extends Model
{
    /**
     * Central DB only (never the tenant connection).
     */
    public function getConnectionName(): string
    {
        return (string) config('tenancy.database.central_connection', config('database.default'));
    }

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
