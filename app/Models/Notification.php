<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'channel',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Get the notification type label.
     */
    public static function getTypeLabel(string $type): string
    {
        $labels = [
            'new_tenant' => 'New Tenant Registration',
            'tenant_suspended' => 'Tenant Suspended',
            'tenant_reactivated' => 'Tenant Reactivated',
            'subscription_created' => 'New Subscription',
            'subscription_renewed' => 'Subscription Renewed',
            'subscription_expiring' => 'Subscription Expiring',
            'subscription_expired' => 'Subscription Expired',
            'subscription_cancelled' => 'Subscription Cancelled',
            'new_support_ticket' => 'New Support Ticket',
            'support_ticket_replied' => 'Support Ticket Reply',
            'feature_request' => 'Feature Request',
            'new_feature_published' => 'New Feature Available',
            'payment_received' => 'Payment Received',
            'payment_failed' => 'Payment Failed',
            'system_alert' => 'System Alert',
        ];

        return $labels[$type] ?? $type;
    }

    /**
     * Get icon for notification type.
     */
    public static function getTypeIcon(string $type): string
    {
        $icons = [
            'new_tenant' => 'building',
            'tenant_suspended' => 'pause-circle',
            'tenant_reactivated' => 'play-circle',
            'subscription_created' => 'credit-card',
            'subscription_renewed' => 'refresh',
            'subscription_expiring' => 'exclamation',
            'subscription_expired' => 'x-circle',
            'subscription_cancelled' => 'ban',
            'new_support_ticket' => 'ticket',
            'support_ticket_replied' => 'chat',
            'feature_request' => 'lightbulb',
            'new_feature_published' => 'star',
            'payment_received' => 'check-circle',
            'payment_failed' => 'exclamation-circle',
            'system_alert' => 'bell',
        ];

        return $icons[$type] ?? 'bell';
    }
}
