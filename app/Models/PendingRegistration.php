<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingRegistration extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'subdomain',
        'clinic_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'street',
        'barangay',
        'city',
        'province',
        'password',
        'subscription_plan_id',
        'billing_cycle',
        'stripe_payment_intent_id',
        'stripe_session_id',
        'amount_paid',
        'status',
        'verification_token',
        'expires_at',
        'approved_at',
        'rejected_at',
        'admin_rejection_message',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Get the subscription plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class , 'subscription_plan_id');
    }

    /**
     * Check if the registration has expired
     */
    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    /**
     * Check if the registration is still pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && !$this->isExpired();
    }

    /**
     * Generate verification URL for the tenant
     */
    public function getVerificationUrl(): string
    {
        return config('app.url') . '/tenant/verify/' . $this->verification_token;
    }

    /**
     * Get full name of the owner
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Scope to get only pending registrations
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get expired pending registrations
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('expires_at', '<', now());
    }

    /**
     * Generate a unique verification token
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
