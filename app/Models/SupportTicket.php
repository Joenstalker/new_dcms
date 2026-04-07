<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $connection = 'central';
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'subject',
        'description',
        'status',
        'priority',
        'category',
        'assigned_to',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'pending']);
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class , 'support_ticket_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(SupportMessage::class , 'support_ticket_id')->latestOfMany();
    }

    public function tenant()
    {
        return $this->belongsTo(\Stancl\Tenancy\Database\Models\Tenant::class , 'tenant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class , 'user_id');
    }
}
