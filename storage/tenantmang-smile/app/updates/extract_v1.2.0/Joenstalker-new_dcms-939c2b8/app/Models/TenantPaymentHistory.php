<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantPaymentHistory extends Model
{
    protected $table = 'payment_histories';

    protected $fillable = [
        'central_history_id',
        'transaction_code',
        'status',
        'transaction_type',
        'plan_name',
        'amount',
        'currency',
        'payment_method_label',
        'description',
        'billed_to_name',
        'billed_to_email',
        'billed_to_address',
        'stripe_payment_intent_id',
        'stripe_invoice_id',
        'stripe_charge_id',
        'stripe_refund_id',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];
}
