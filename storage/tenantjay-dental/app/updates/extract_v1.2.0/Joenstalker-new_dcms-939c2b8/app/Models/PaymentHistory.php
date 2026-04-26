<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string|null $tenant_id
 * @property int|null $subscription_id
 * @property int|null $pending_registration_id
 * @property string $transaction_code
 * @property string $status
 * @property string $transaction_type
 */
class PaymentHistory extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'pending_registration_id',
        'transaction_code',
        'status',
        'transaction_type',
        'plan_name',
        'amount',
        'currency',
        'payment_method_label',
        'payment_method_brand',
        'payment_method_last4',
        'description',
        'billed_to_name',
        'billed_to_email',
        'billed_to_address',
        'stripe_event_id',
        'stripe_event_type',
        'stripe_session_id',
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function pendingRegistration(): BelongsTo
    {
        return $this->belongsTo(PendingRegistration::class);
    }
}
