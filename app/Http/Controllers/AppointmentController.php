<?php

namespace App\Http\Controllers;

use App\Events\TenantAppointmentChanged;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\NotificationTriggerService;
use App\Services\TenantNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with(['patient', 'dentist'])->latest('appointment_date')->get();
        $dentists = User::role('Dentist')->get(['id', 'name', 'calendar_color']);

        return Inertia::render('Tenant/Appointments/Index', [
            'appointments' => $appointments,
            'patients' => Patient::select('id', 'first_name', 'last_name')->get(),
            'dentists' => $dentists,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'nullable|exists:patients,id',
            'guest_first_name' => 'required_without:patient_id|string|max:255|nullable',
            'guest_last_name' => 'required_without:patient_id|string|max:255|nullable',
            'guest_phone' => 'required_without:patient_id|string|max:20|nullable',
            'appointment_date' => 'required|date',
            'service' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|string|in:scheduled,completed,cancelled,walk-in',
        ]);

        $appointment = Appointment::create($validated);

        // Send in-app notification to owners
        $notificationService = app(TenantNotificationService::class);
        $patientName = $appointment->patient
            ? $appointment->patient->first_name . ' ' . $appointment->patient->last_name
            : ($appointment->guest_first_name . ' ' . $appointment->guest_last_name);

        $notificationService->notifyAppointmentCreated(
            auth()->user(),
        [
            'id' => $appointment->id,
            'date' => $appointment->appointment_date->format('M d, Y - h:i A'),
            'patient_name' => $patientName,
        ]
        );

        // Send email notification (gated by subscription feature)
        $appointment->load(['patient', 'dentist']);
        app(NotificationTriggerService::class)->onBookingCreated($appointment);
        $this->broadcastAppointmentChange($appointment, 'created');

        return redirect()->route('tenant.appointments.index')->with('success', 'Appointment scheduled successfully.');
    }

    public function update(Request $request, $appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        $validated = $request->validate([
            'status' => 'required|string|in:scheduled,completed,cancelled,walk-in',
            'appointment_date' => 'sometimes|required|date',
        ]);

        $oldStatus = $appointment->status;
        $appointment->update($validated);

        // Send notification if status changed
        if ($oldStatus !== $validated['status']) {
            $notificationService = app(TenantNotificationService::class);
            $patientName = $appointment->patient
                ? $appointment->patient->first_name . ' ' . $appointment->patient->last_name
                : ($appointment->guest_first_name . ' ' . $appointment->guest_last_name);

            if ($validated['status'] === 'cancelled') {
                $notificationService->notifyOwners(
                    'appointment_cancelled',
                    'Appointment Cancelled',
                    "Appointment for {$patientName} on {$appointment->appointment_date->format('M d, Y')} has been cancelled",
                [
                    'appointment_id' => $appointment->id,
                    'patient_name' => $patientName,
                ],
                    auth()->user()
                );
            }
            else {
                $notificationService->notifyOwners(
                    'appointment_updated',
                    'Appointment Updated',
                    "Appointment for {$patientName} has been updated to {$validated['status']}",
                [
                    'appointment_id' => $appointment->id,
                    'patient_name' => $patientName,
                    'new_status' => $validated['status'],
                ],
                    auth()->user()
                );
            }
        }

        $appointment->load(['patient', 'dentist']);
        $this->broadcastAppointmentChange($appointment, 'updated');

        return redirect()->back()->with('success', 'Appointment updated.');
    }

    public function approve($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        if (!$appointment->patient_id) {
            $medicalHistoryString = is_array($appointment->guest_medical_history)
                ? implode(', ', $appointment->guest_medical_history)
                : $appointment->guest_medical_history;

            $patientPhotoPath = $this->normalizePhotoForPatient($appointment->photo_path);

            // Create a new patient from guest details
            $patient = Patient::create([
                'first_name' => $appointment->guest_first_name,
                'last_name' => $appointment->guest_last_name,
                'phone' => $appointment->guest_phone,
                'email' => $appointment->guest_email,
                'address' => $appointment->guest_address,
                'medical_history' => $medicalHistoryString,
                'notes' => 'Created from booking ' . $appointment->booking_reference . ' on ' . now()->format('Y-m-d'),
                'photo_path' => $patientPhotoPath,
            ]);

            $appointment->update([
                'patient_id' => $patient->id,
                'status' => 'scheduled',
                'guest_first_name' => null,
                'guest_last_name' => null,
                'guest_phone' => null,
                'guest_email' => null,
                'guest_address' => null,
                'guest_medical_history' => null,
                'photo_path' => null,
            ]);
        }
        else {
            if ($appointment->photo_path && $appointment->patient) {
                $appointment->patient->update([
                    'photo_path' => $this->normalizePhotoForPatient($appointment->photo_path),
                ]);
            }

            $appointment->update([
                'status' => 'scheduled',
                'photo_path' => null,
            ]);
        }

        $appointment->load(['patient', 'dentist']);
        app(NotificationTriggerService::class)->onBookingApproved($appointment);
        $this->broadcastAppointmentChange($appointment, 'approved');

        return redirect()->back()->with('success', 'Appointment approved and patient registered.');
    }

    public function reject($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        $appointment->update(['status' => 'cancelled']);

        $appointment->load(['patient', 'dentist']);
        app(NotificationTriggerService::class)->onBookingRejected($appointment);
        $this->broadcastAppointmentChange($appointment, 'rejected');

        return redirect()->back()->with('success', 'Appointment rejected.');
    }

    public function destroy($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        $patientName = $appointment->patient
            ? $appointment->patient->first_name . ' ' . $appointment->patient->last_name
            : ($appointment->guest_first_name . ' ' . $appointment->guest_last_name);

        $notificationService = app(TenantNotificationService::class);
        $notificationService->notifyOwners(
            'appointment_deleted',
            'Appointment Deleted',
            "Appointment for {$patientName} has been deleted",
        [
            'appointment_id' => $appointment->id,
            'patient_name' => $patientName,
        ],
            auth()->user()
        );

        $deletedPayload = [
            'id' => $appointment->id,
        ];

        $appointment->delete();
        $this->broadcastRawAppointmentChange('deleted', $deletedPayload);

        return redirect()->route('tenant.appointments.index')->with('success', 'Appointment deleted.');
    }

    private function broadcastAppointmentChange(Appointment $appointment, string $action): void
    {
        if (!tenant()) {
            return;
        }

        $appointment->loadMissing(['patient', 'dentist']);

        $payload = [
            'id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'dentist_id' => $appointment->dentist_id,
            'guest_first_name' => $appointment->guest_first_name,
            'guest_last_name' => $appointment->guest_last_name,
            'guest_phone' => $appointment->guest_phone,
            'appointment_date' => optional($appointment->appointment_date)?->toISOString(),
            'status' => $appointment->status,
            'type' => $appointment->type,
            'service' => $appointment->service,
            'notes' => $appointment->notes,
            'photo_path' => $appointment->photo_path,
            'patient' => $appointment->patient ? [
                'id' => $appointment->patient->id,
                'first_name' => $appointment->patient->first_name,
                'last_name' => $appointment->patient->last_name,
                'phone' => $appointment->patient->phone,
                'email' => $appointment->patient->email,
                'photo_path' => $appointment->patient->photo_path,
                'photo_url' => $appointment->patient->photo_url ?? null,
            ] : null,
            'dentist' => $appointment->dentist ? [
                'id' => $appointment->dentist->id,
                'name' => $appointment->dentist->name,
            ] : null,
        ];

        $this->broadcastRawAppointmentChange($action, $payload);
    }

    private function broadcastRawAppointmentChange(string $action, array $appointmentPayload): void
    {
        if (!tenant()) {
            return;
        }

        try {
            if (config('broadcasting.default') && config('broadcasting.default') !== 'null') {
                broadcast(new TenantAppointmentChanged((string) tenant()->getTenantKey(), $action, $appointmentPayload));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to broadcast appointment change', [
                'action' => $action,
                'error' => $e->getMessage(),
                'tenant_id' => tenant()->id,
            ]);
        }
    }

    private function normalizePhotoForPatient(?string $photoPath): ?string
    {
        if (empty($photoPath)) {
            return null;
        }

        if (str_starts_with($photoPath, 'data:image')) {
            return $photoPath;
        }

        try {
            if (!Storage::disk('public')->exists($photoPath)) {
                return $photoPath;
            }

            $content = Storage::disk('public')->get($photoPath);
            $mimeType = Storage::disk('public')->mimeType($photoPath) ?: 'image/jpeg';

            return 'data:' . $mimeType . ';base64,' . base64_encode($content);
        } catch (\Throwable $e) {
            return $photoPath;
        }
    }
}
