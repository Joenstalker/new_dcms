<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Create a notification for a user.
     */
    public function create(User $user, string $type, string $title, string $message, array $data = [], string $channel = 'database'): Notification
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channel' => $channel,
        ]);

        // Send email if enabled
        if ($channel === 'both' || $channel === 'email') {
            $this->sendEmailNotification($user, $notification);
        }

        return $notification;
    }

    /**
     * Create a notification for admin users.
     */
    public function notifyAdmins(string $type, string $title, string $message, array $data = [], string $channel = 'database'): void
    {
        $admins = User::on('central')->where('is_admin', true)->get();

        foreach ($admins as $admin) {
            // Check if user has this notification type enabled
            $setting = NotificationSetting::on('central')
                ->where('user_id', $admin->id)
                ->where('type', $type)
                ->first();

            // If setting doesn't exist, use default (enabled)
            $shouldNotify = !$setting || $setting->enabled;

            if ($shouldNotify) {
                $effectiveChannel = $setting ? $setting->channel : $channel;
                $this->create($admin, $type, $title, $message, $data, $effectiveChannel);
            }
        }
    }

    /**
     * Send email notification.
     */
    protected function sendEmailNotification(User $user, Notification $notification): void
    {
        // For now, we'll use a simple approach
        // In production, you'd create Mailable classes
        try {
            // You can implement email sending here
            // Example: Mail::to($user->email)->send(new NotificationMail($notification));

            // For now, we'll just log it
            \Log::info("Email notification would be sent to {$user->email}: {$notification->title}");
        }
        catch (\Exception $e) {
            \Log::error("Failed to send email notification: " . $e->getMessage());
        }
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->count();
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->unread()
            ->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Delete old notifications (older than X days).
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        return Notification::on('central')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Send subscription expiring notifications.
     */
    public function sendSubscriptionExpiringNotifications(): void
    {
        // This would be called by a scheduled job
        // Get subscriptions expiring in 7 days
        $subscriptions = \App\Models\Subscription::where('stripe_status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [
            now(),
            now()->addDays(7),
        ])
            ->with('tenant')
            ->get();

        foreach ($subscriptions as $subscription) {
            $this->notifyAdmins(
                'subscription_expiring',
                'Subscription Expiring',
                "Tenant {$subscription->tenant->name} subscription expires on {$subscription->ends_at->format('M d, Y')}",
            [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'expires_at' => $subscription->ends_at->toISOString(),
            ]
            );
        }
    }
}
