<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
        'region',
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
        // Enhancement fields
        'pending_timeout_minutes',
        'reminder_enabled',
        'reminder_sent_at',
        'auto_approve_enabled',
        'auto_approve_scheduled_at',
        'pending_refund_timer_enabled',
        'pending_refund_timer_minutes',
        'original_expires_at',
        'expiry_history',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'amount_paid' => 'decimal:2',
        'reminder_enabled' => 'boolean',
        'auto_approve_enabled' => 'boolean',
        'pending_refund_timer_enabled' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'auto_approve_scheduled_at' => 'datetime',
        'original_expires_at' => 'datetime',
        'expiry_history' => 'array',
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
        return now('UTC')->greaterThan($this->expires_at);
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
            ->where('expires_at', '<', now('UTC'));
    }

    /**
     * Generate a unique verification token
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get the effective pending timeout minutes (per-registration or global default)
     */
    public function getEffectiveTimeoutMinutes(): int
    {
        if ($this->pending_timeout_minutes) {
            return $this->pending_timeout_minutes;
        }

        return SystemSetting::get('pending_timeout_default_minutes', 10080); // Default 7 days
    }

    /**
     * Check if reminder is enabled (per-registration or global default)
     */
    public function isReminderEnabled(): bool
    {
        if ($this->reminder_enabled !== null) {
            return $this->reminder_enabled;
        }

        return SystemSetting::get('pending_reminder_global_enabled', true);
    }

    /**
     * Check if auto-approve is enabled (per-registration or global default)
     */
    public function isAutoApproveEnabled(): bool
    {
        if ($this->auto_approve_enabled !== null) {
            return $this->auto_approve_enabled;
        }

        return SystemSetting::get('pending_auto_approve_enabled', false);
    }

    /**
     * Get the effective auto-approve minutes
     */
    public function getEffectiveAutoApproveMinutes(): int
    {
        return SystemSetting::get('pending_auto_approve_minutes', 10080);
    }

    /**
     * Check if auto-refund is enabled (per-registration or global default)
     */
    public function isAutoRefundEnabled(): bool
    {
        if ($this->pending_refund_timer_enabled !== null) {
            return $this->pending_refund_timer_enabled;
        }

        return SystemSetting::get('pending_refund_timer_enabled', false);
    }

    /**
     * Get the effective auto-refund minutes
     */
    public function getEffectiveAutoRefundMinutes(): int
    {
        if ($this->pending_refund_timer_minutes) {
            return $this->pending_refund_timer_minutes;
        }

        return SystemSetting::get('pending_refund_timer_minutes', 10080);
    }

    /**
     * Get time remaining until expiry in a human-readable format
     */
    public function getTimeRemainingAttribute(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        $diff = now('UTC')->diff($this->expires_at);

        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ' . $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
        }

        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ' . $diff->i . ' min';
        }

        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    }

    /**
     * Get seconds remaining until expiry
     */
    public function getSecondsRemainingAttribute(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now('UTC')->diffInSeconds($this->expires_at);
    }

    /**
     * Extend the pending time
     */
    public function extendTime(int $minutes): bool
    {
        $oldExpiresAt = $this->expires_at;
        $newExpiresAt = $this->expires_at->addMinutes($minutes);

        // Store original expiry if not already stored
        if (!$this->original_expires_at) {
            $this->original_expires_at = $this->expires_at;
        }

        // Update expiry history
        $history = $this->expiry_history ?? [];
        $history[] = [
            'action' => 'extended',
            'previous_expires_at' => $oldExpiresAt->toIso8601String(),
            'new_expires_at' => $newExpiresAt->toIso8601String(),
            'minutes_added' => $minutes,
            'timestamp' => now('UTC')->toIso8601String(),
        ];

        return $this->update([
            'expires_at' => $newExpiresAt,
            'expiry_history' => $history,
        ]);
    }

    /**
     * Set a new expiry time
     */
    public function setExpiryTime(\Carbon\Carbon $newExpiresAt): bool
    {
        $oldExpiresAt = $this->expires_at;

        // Store original expiry if not already stored
        if (!$this->original_expires_at) {
            $this->original_expires_at = $this->expires_at;
        }

        // Update expiry history
        $history = $this->expiry_history ?? [];
        $history[] = [
            'action' => 'set_time',
            'previous_expires_at' => $oldExpiresAt->toIso8601String(),
            'new_expires_at' => $newExpiresAt->toIso8601String(),
            'timestamp' => now('UTC')->toIso8601String(),
        ];

        return $this->update([
            'expires_at' => $newExpiresAt,
            'expiry_history' => $history,
        ]);
    }

    /**
     * Scope to get registrations that need reminders
     */
    public function scopeNeedingReminders($query)
    {
        $reminderMinutes = SystemSetting::get('pending_reminder_minutes_before', 1440);

        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) use ($reminderMinutes) {
            $q->whereNull('reminder_sent_at')
                ->orWhereRaw('expires_at > DATE_ADD(reminder_sent_at, INTERVAL ? MINUTE)', [$reminderMinutes]);
        })
            ->where('expires_at', '>', now('UTC'))
            ->where('expires_at', '<=', now('UTC')->addMinutes($reminderMinutes));
    }

    /**
     * Scope to get registrations eligible for auto-approve
     */
    public function scopeEligibleForAutoApprove($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
            $q->where('auto_approve_enabled', true)
                ->orWhere(function ($sub) {
                $sub->whereNull('auto_approve_enabled')
                    ->whereRaw('? = true', [SystemSetting::get('pending_auto_approve_enabled', false)]);
            }
            );
        })
            ->where('expires_at', '<', now('UTC'));
    }
}
