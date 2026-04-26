<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read SupportMessage|null $message
 */
class SupportAttachment extends Model
{
    protected $connection = 'central';

    use HasFactory;

    protected $fillable = [
        'support_message_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    protected $appends = [
        'url',
    ];

    public function message()
    {
        return $this->belongsTo(SupportMessage::class, 'support_message_id');
    }

    public function getUrlAttribute(): string
    {
        $path = (string) ($this->file_path ?? '');
        if ($path === '') {
            return '';
        }

        // Use the current request host (tenant/central) to avoid CSP cross-origin blocks.
        return asset('storage/'.ltrim($path, '/'));
    }
}
