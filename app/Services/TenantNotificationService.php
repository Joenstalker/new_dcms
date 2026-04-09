<?php

namespace App\Services;

use App\Events\UserNotificationCreated;
use App\Models\TenantNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class TenantNotificationService
{
    /**
     * Create a notification for a tenant user.
     */
    public function create(User $user, string $type, string $title, string $message, array $data = [], ?User $sender = null): TenantNotification
    {
        $notificationData = [
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ];

        if ($sender) {
            $notificationData['sender_type'] = 'User';
            $notificationData['sender_id'] = $sender->id;
        }

        $notification = TenantNotification::create($notificationData);

        broadcast(new UserNotificationCreated(
            (int) $user->id,
            'tenant',
            [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'is_read' => (bool) $notification->is_read,
                'created_at' => optional($notification->created_at)?->toISOString(),
            ],
            $this->getUnreadCount($user)
        ));

        return $notification;
    }

    /**
     * Create a notification for multiple users (e.g., all owners).
     */
    public function notifyUsers(array $userIds, string $type, string $title, string $message, array $data = [], ?User $sender = null): void
    {
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->create($user, $type, $title, $message, $data, $sender);
            }
        }
    }

    /**
     * Notify all owners in the tenant.
     */
    public function notifyOwners(string $type, string $title, string $message, array $data = [], ?User $sender = null): void
    {
        $owners = User::role('Owner')->get();

        foreach ($owners as $owner) {
            $this->create($owner, $type, $title, $message, $data, $sender);
        }
    }

    /**
     * Notify staff members (dentists and assistants).
     */
    public function notifyStaff(string $type, string $title, string $message, array $data = [], ?User $sender = null): void
    {
        $staff = User::role(['Dentist', 'Assistant'])->get();

        foreach ($staff as $member) {
            $this->create($member, $type, $title, $message, $data, $sender);
        }
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return TenantNotification::where('user_id', $user->id)
            ->unread()
            ->count();
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(TenantNotification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): void
    {
        TenantNotification::where('user_id', $user->id)
            ->unread()
            ->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Create appointment-related notifications.
     */
    public function notifyAppointmentCreated(User $creator, array $appointmentData): void
    {
        // Notify all owners
        $this->notifyOwners(
            'appointment_created',
            'New Appointment Scheduled',
            "A new appointment has been scheduled for {$appointmentData['date']}",
        [
            'appointment_id' => $appointmentData['id'] ?? null,
            'patient_name' => $appointmentData['patient_name'] ?? null,
            'appointment_date' => $appointmentData['date'] ?? null,
        ],
            $creator
        );
    }

    /**
     * Create patient-related notifications.
     */
    public function notifyPatientAdded(User $creator, array $patientData): void
    {
        $this->notifyOwners(
            'patient_added',
            'New Patient Added',
            "A new patient '{$patientData['name']}' has been added to the system",
        [
            'patient_id' => $patientData['id'] ?? null,
            'patient_name' => $patientData['name'] ?? null,
        ],
            $creator
        );
    }

    /**
     * Create treatment completion notifications.
     */
    public function notifyTreatmentCompleted(User $creator, array $treatmentData): void
    {
        $this->notifyOwners(
            'treatment_completed',
            'Treatment Completed',
            "Treatment '{$treatmentData['name']}' has been completed for {$treatmentData['patient_name']}",
        [
            'treatment_id' => $treatmentData['id'] ?? null,
            'patient_name' => $treatmentData['patient_name'] ?? null,
            'treatment_name' => $treatmentData['name'] ?? null,
        ],
            $creator
        );
    }

    /**
     * Send system notification to all tenant users.
     */
    public function broadcastToTenant(string $type, string $title, string $message, array $data = []): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $this->create($user, $type, $title, $message, $data);
        }
    }

    /**
     * Send new feature notification to all tenant users.
     */
    public function notifyNewFeature(string $featureName, string $description): void
    {
        $this->broadcastToTenant(
            'new_feature',
            'New Feature Available',
            "A new feature '$featureName' is now available. $description",
        [
            'feature_name' => $featureName,
            'description' => $description,
        ]
        );
    }

    /**
     * Send subscription reminder to all owners.
     */
    public function notifySubscriptionReminder(string $message, array $data = []): void
    {
        $this->notifyOwners(
            'subscription_reminder',
            'Subscription Reminder',
            $message,
            $data
        );
    }

    /**
     * Send payment due notification to owners.
     */
    public function notifyPaymentDue(string $message, array $data = []): void
    {
        $this->notifyOwners(
            'payment_due',
            'Payment Due',
            $message,
            $data
        );
    }
}
