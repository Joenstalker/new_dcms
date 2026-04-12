<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantFeatureUpdate extends Model
{
    protected $connection = 'central';

    protected $table = 'tenant_feature_updates';

    public function getConnectionName()
    {
        if (app()->runningUnitTests()) {
            // When tenancy is active, default is switched to `tenant`; central data must stay on the central connection.
            return (string) config('tenancy.database.central_connection', config('database.default'));
        }

        return parent::getConnectionName();
    }

    protected $fillable = [
        'tenant_id',
        'feature_id',
        'status',
        'applied_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_DISMISSED = 'dismissed';

    /**
     * Get the feature associated with this update.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    /**
     * Check if the update has been applied.
     */
    public function isApplied(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    /**
     * Check if the update is still pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark this update as applied.
     */
    public function markAsApplied(): void
    {
        $this->update([
            'status' => self::STATUS_APPLIED,
            'applied_at' => now(),
        ]);
    }

    /**
     * Mark this update as dismissed.
     */
    public function markAsDismissed(): void
    {
        $this->update([
            'status' => self::STATUS_DISMISSED,
        ]);
    }

    /**
     * Scope to get pending updates.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get applied updates.
     */
    public function scopeApplied($query)
    {
        return $query->where('status', self::STATUS_APPLIED);
    }
}
