<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantNotification extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'sender_type',
        'sender_id',
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
     * Get the sender of the notification.
     */
    public function sender()
    {
        if ($this->sender_type === 'User') {
            return $this->belongsTo(User::class , 'sender_id');
        }
        return null;
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
            'appointment_created' => 'Appointment Created',
            'appointment_updated' => 'Appointment Updated',
            'appointment_cancelled' => 'Appointment Cancelled',
            'patient_added' => 'New Patient',
            'patient_updated' => 'Patient Updated',
            'treatment_completed' => 'Treatment Completed',
            'new_feature' => 'New Feature Available',
            'subscription_reminder' => 'Subscription Reminder',
            'payment_due' => 'Payment Due',
            'staff_message' => 'Staff Message',
            'system_notification' => 'System Notification',
        ];

        return $labels[$type] ?? $type;
    }

    /**
     * Get icon for notification type.
     */
    public static function getTypeIcon(string $type): string
    {
        $icons = [
            'appointment_created' => 'calendar-plus',
            'appointment_updated' => 'calendar',
            'appointment_cancelled' => 'calendar-x',
            'patient_added' => 'user-plus',
            'patient_updated' => 'user',
            'treatment_completed' => 'check-circle',
            'new_feature' => 'star',
            'subscription_reminder' => 'clock',
            'payment_due' => 'credit-card',
            'staff_message' => 'message',
            'system_notification' => 'bell',
        ];

        return $icons[$type] ?? 'bell';
    }
}
