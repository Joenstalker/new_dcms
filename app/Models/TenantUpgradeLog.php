<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUpgradeLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'from_version',
        'to_version',
        'status',
        'log_output',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class , 'tenant_id');
    }
}
