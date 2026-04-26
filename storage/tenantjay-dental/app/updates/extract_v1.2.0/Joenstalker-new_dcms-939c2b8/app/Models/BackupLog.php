<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'status',
        'started_at',
        'completed_at',
        'file_path',
        'file_size',
        'drive_file_id',
        'backup_details',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'backup_details' => 'array',
        'file_size' => 'integer',
    ];

    /**
     * Get the formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get the duration of the backup
     */
    public function getDurationAttribute(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return (int) $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Scope for successful backups
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed backups
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
