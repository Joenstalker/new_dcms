<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\NotificationTriggerService;
use App\Services\TenantNotificationService;
use Illuminate\Http\Request;
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

        return redirect()->route('tenant.appointments.index')->with('success', 'Appointment scheduled successfully.');
    }

    public function update(Request $request, Appointment $appointment)
    {
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

        return redirect()->back()->with('success', 'Appointment updated.');
    }

    public function approve(Appointment $appointment)
    {
        if (!$appointment->patient_id) {
            $medicalHistoryString = is_array($appointment->guest_medical_history) 
                ? implode(', ', $appointment->guest_medical_history) 
                : $appointment->guest_medical_history;

            // Create a new patient from guest details
            $patient = Patient::create([
                'first_name' => $appointment->guest_first_name,
                'last_name' => $appointment->guest_last_name,
                'phone' => $appointment->guest_phone,
                'email' => $appointment->guest_email,
                'address' => $appointment->guest_address,
                'medical_history' => $medicalHistoryString,
                'notes' => 'Created from booking ' . $appointment->booking_reference . ' on ' . now()->format('Y-m-d'),
                'photo_path' => $appointment->photo_path,
            ]);

            $appointment->update([
                'patient_id' => $patient->id,
                'status' => 'scheduled', // Keep status as scheduled, or change to 'approved' if that's a valid status
                'guest_first_name' => null,
                'guest_last_name' => null,
                'guest_phone' => null,
                'guest_email' => null,
                'guest_address' => null,
                'guest_medical_history' => null,
                'photo_path' => null, // Clear from appointment as it's now in patient
            ]);
        } else {
            // If patient_id already exists, just update the status
            $appointment->status = 'scheduled';
            $appointment->save();
        }

        // Send email notification for approval (gated by subscription feature)
        $appointment->load(['patient', 'dentist']);
        app(NotificationTriggerService::class)->onBookingApproved($appointment);

        return redirect()->back()->with('success', 'Appointment approved and patient registered.');
    }

    public function destroy(Appointment $appointment)
    {
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

        $appointment->delete();

        return redirect()->route('tenant.appointments.index')->with('success', 'Appointment deleted.');
    }
}
