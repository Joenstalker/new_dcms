<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    protected $connection = 'central';
    use HasFactory;

    protected $fillable = [
        'support_ticket_id',
        'sender_id',
        'sender_type',
        'content',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class , 'support_ticket_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class , 'sender_id');
    }

    public function attachments()
    {
        return $this->hasMany(SupportAttachment::class , 'support_message_id');
    }
}
