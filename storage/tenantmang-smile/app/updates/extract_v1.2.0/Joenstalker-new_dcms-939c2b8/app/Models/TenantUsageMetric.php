<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TenantUsageMetric extends Model
{
    protected $connection = 'central';

    protected $table = 'tenant_usage_metrics';

    protected $fillable = [
        'tenant_id',
        'date',
        'bandwidth_bytes',
        'request_count',
        'api_request_count',
        'public_request_count',
        'file_used_bytes',
        'db_used_bytes',
        'total_used_bytes',
    ];

    protected $casts = [
        'date' => 'date',
        'bandwidth_bytes' => 'integer',
        'request_count' => 'integer',
        'api_request_count' => 'integer',
        'public_request_count' => 'integer',
        'file_used_bytes' => 'integer',
        'db_used_bytes' => 'integer',
        'total_used_bytes' => 'integer',
    ];

    public function getConnectionName()
    {
        if (app()->runningUnitTests()) {
            return config('database.default');
        }

        return parent::getConnectionName();
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }
}
