<?php

namespace App\Notifications;

use App\Mail\ClinicPasswordReset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClinicPasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable)
    {
        $resetLink = url('/reset-password/' . $this->token . '?email=' . urlencode($notifiable->email));

        // Get tenant information if in tenant context
        $tenant = null;
        $brandingColor = '#3b82f6';

        try {
            $tenant = tenancy()->tenant();
            if ($tenant) {
                $brandingColor = $tenant->branding_color ?? '#3b82f6';
            }
        } catch (\Exception $e) {
            // Not in tenant context, use default
        }

        return (new ClinicPasswordReset($notifiable, $resetLink, $tenant?->name, $brandingColor))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
