<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'enabled',
        'channel',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Get the user that owns the notification setting.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all available notification types with their defaults.
     */
    public static function getDefaultSettings(): array
    {
        return [
            // Admin notification types
            [
                'type' => 'new_tenant',
                'label' => 'New Tenant Registration',
                'description' => 'Get notified when a new tenant signs up',
                'default_enabled' => true,
                'channels' => ['database', 'email'],
            ],
            [
                'type' => 'subscription_created',
                'label' => 'New Subscription',
                'description' => 'Get notified when a tenant subscribes',
                'default_enabled' => true,
                'channels' => ['database', 'email'],
            ],
            [
                'type' => 'subscription_expiring',
                'label' => 'Subscription Expiring',
                'description' => 'Get notified 7 days before subscription expires',
                'default_enabled' => true,
                'channels' => ['database', 'email'],
            ],
            [
                'type' => 'subscription_expired',
                'label' => 'Subscription Expired',
                'description' => 'Get notified when a subscription expires',
                'default_enabled' => true,
                'channels' => ['database', 'email'],
            ],
            [
                'type' => 'new_support_ticket',
                'label' => 'New Support Ticket',
                'description' => 'Get notified when a tenant submits a support ticket',
                'default_enabled' => true,
                'channels' => ['database', 'email'],
            ],
            [
                'type' => 'feature_request',
                'label' => 'Feature Request',
                'description' => 'Get notified when a tenant requests a feature',
                'default_enabled' => true,
                'channels' => ['database'],
            ],
            [
                'type' => 'payment_received',
                'label' => 'Payment Received',
                'description' => 'Get notified when a payment is received',
                'default_enabled' => true,
                'channels' => ['database', 'email'],
            ],
            [
                'type' => 'payment_failed',
                'label' => 'Payment Failed',
                'description' => 'Get notified when a payment fails',
                'default_enabled' => true,
                'channels' => ['database', 'email'],
            ],
            // Tenant notification types
            [
                'type' => 'appointment_created',
                'label' => 'Appointment Created',
                'description' => 'Get notified when a new appointment is scheduled',
                'default_enabled' => true,
                'channels' => ['database'],
            ],
            [
                'type' => 'appointment_updated',
                'label' => 'Appointment Updated',
                'description' => 'Get notified when an appointment is updated',
                'default_enabled' => true,
                'channels' => ['database'],
            ],
            [
                'type' => 'appointment_cancelled',
                'label' => 'Appointment Cancelled',
                'description' => 'Get notified when an appointment is cancelled',
                'default_enabled' => true,
                'channels' => ['database'],
            ],
            [
                'type' => 'patient_added',
                'label' => 'New Patient',
                'description' => 'Get notified when a new patient is added',
                'default_enabled' => true,
                'channels' => ['database'],
            ],
            [
                'type' => 'treatment_completed',
                'label' => 'Treatment Completed',
                'description' => 'Get notified when a treatment is completed',
                'default_enabled' => true,
                'channels' => ['database'],
            ],
            [
                'type' => 'new_feature',
                'label' => 'New Feature Available',
                'description' => 'Get notified when new features are available',
                'default_enabled' => true,
                'channels' => ['database', 'email'],
            ],
            [
                'type' => 'subscription_reminder',
                'label' => 'Subscription Reminder',
                'description' => 'Get reminders about your subscription',
                'default_enabled' => true,
                'channels' => ['database', 'email'],
            ],
            [
                'type' => 'payment_due',
                'label' => 'Payment Due',
                'description' => 'Get notified about upcoming payments',
                'default_enabled' => true,
                'channels' => ['database', 'email'],
            ],
            [
                'type' => 'staff_message',
                'label' => 'Staff Message',
                'description' => 'Get messages from staff members',
                'default_enabled' => true,
                'channels' => ['database'],
            ],
        ];
    }

    /**
     * Get only admin notification types.
     */
    public static function getAdminTypes(): array
    {
        return array_filter(self::getDefaultSettings(), function ($type) {
            return in_array($type['type'], [
                'new_tenant',
                'subscription_created',
                'subscription_expiring',
                'subscription_expired',
                'new_support_ticket',
                'feature_request',
                'payment_received',
                'payment_failed',
            ]);
        });
    }

    /**
     * Get only tenant notification types.
     */
    public static function getTenantTypes(): array
    {
        return array_filter(self::getDefaultSettings(), function ($type) {
            return in_array($type['type'], [
                'appointment_created',
                'appointment_updated',
                'appointment_cancelled',
                'patient_added',
                'treatment_completed',
                'new_feature',
                'subscription_reminder',
                'payment_due',
                'staff_message',
            ]);
        });
    }
}
