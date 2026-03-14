<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'description',
        'metadata',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * The admin user who performed the action.
     */
    public function admin()
    {
        return $this->belongsTo(User::class , 'admin_id');
    }

    /**
     * Log an admin action.
     */
    public static function record(
        string $action,
        string $description,
        ?string $targetType = null,
        ?string $targetId = null,
        array $metadata = []
        ): self
    {
        return static::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
