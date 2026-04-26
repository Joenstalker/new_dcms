<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'message',
        'status',
        'ip_address',
        'recaptcha_score',
        'admin_notes',
    ];

    protected $casts = [
        'recaptcha_score' => 'float',
    ];

    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNot('status', 'archived');
    }
}
