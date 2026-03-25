<?php

namespace App\Services;

use App\Mail\AppointmentConfirmation;
use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Centralized service for appointment notifications.
 * 
 * Sends database notifications (via TenantNotificationService) + emails
 * to patients/guests when appointments are created, approved, or upcoming.
 * 
 * Gated by the `sms_notifications` feature flag on the tenant's subscription plan.
 */
class NotificationTriggerService
{
    protected TenantNotificationService $tenantNotificationService;

    public function __construct(TenantNotificationService $tenantNotificationService)
    {
        $this->tenantNotificationService = $tenantNotificationService;
    }

    /**
     * Check if the current tenant has the notification feature enabled.
     */
    public function isEnabled(): bool
    {
        $tenant = tenant();
        if (!$tenant) return false;

        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        if (!$subscription || !$subscription->plan) return false;

        return $subscription->plan->hasFeature('sms_notifications');
    }

    /**
     * Send notification when a new booking is created.
     */
    public function onBookingCreated(Appointment $appointment): void
    {
        if (!$this->isEnabled()) return;

        $email = $this->getPatientEmail($appointment);
        $tenant = tenant();

        if ($email) {
            try {
                Mail::to($email)->queue(new AppointmentConfirmation($appointment, $tenant));
            } catch (\Exception $e) {
                Log::warning('Failed to send appointment confirmation email', [
                    'appointment_id' => $appointment->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send notification when an appointment is approved.
     */
    public function onBookingApproved(Appointment $appointment): void
    {
        if (!$this->isEnabled()) return;

        $email = $this->getPatientEmail($appointment);
        $tenant = tenant();

        if ($email) {
            try {
                Mail::to($email)->queue(new AppointmentConfirmation($appointment, $tenant, true));
            } catch (\Exception $e) {
                Log::warning('Failed to send appointment approval email', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send reminder for appointments happening tomorrow.
     */
    public function onAppointmentReminder(Appointment $appointment): void
    {
        if (!$this->isEnabled()) return;

        $email = $this->getPatientEmail($appointment);
        $tenant = tenant();

        if ($email) {
            try {
                Mail::to($email)->queue(new AppointmentReminder($appointment, $tenant));
            } catch (\Exception $e) {
                Log::warning('Failed to send appointment reminder email', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get the patient's email from the appointment.
     */
    protected function getPatientEmail(Appointment $appointment): ?string
    {
        if ($appointment->patient && $appointment->patient->email) {
            return $appointment->patient->email;
        }

        return $appointment->guest_email;
    }
}
