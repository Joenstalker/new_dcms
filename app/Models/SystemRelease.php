<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemRelease extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'version',
        'release_notes',
        'released_at',
        'is_mandatory',
        'requires_db_update',
    ];

    protected $casts = [
        'released_at' => 'datetime',
        'is_mandatory' => 'boolean',
        'requires_db_update' => 'boolean',
    ];
}
