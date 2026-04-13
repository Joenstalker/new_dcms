<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantFileObject extends Model
{
    protected $connection = 'central';

    protected $table = 'tenant_file_objects';

    protected $fillable = [
        'tenant_id',
        'disk',
        'path',
        'bytes',
        'hash',
    ];

    protected $casts = [
        'bytes' => 'integer',
    ];

    public function getConnectionName()
    {
        if (app()->runningUnitTests()) {
            return config('database.default');
        }

        return parent::getConnectionName();
    }
}

