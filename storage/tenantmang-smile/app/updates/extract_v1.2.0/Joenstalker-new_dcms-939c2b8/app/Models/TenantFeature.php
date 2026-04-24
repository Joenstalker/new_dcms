<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantFeature extends Model
{
    protected $connection = 'central';

    public function getConnectionName()
    {
        if (app()->runningUnitTests()) {
            return config('database.default');
        }

        return parent::getConnectionName();
    }

    protected $fillable = [
        'tenant_id',
        'feature_id',
        'is_enabled',
        'override_reason',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the tenant associated with this feature override.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class , 'tenant_id', 'id');
    }

    /**
     * Get the feature.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
