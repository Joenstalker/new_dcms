<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Services\TenantNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with('patient')->latest('appointment_date')->get();
        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'patients' => Patient::select('id', 'first_name', 'last_name')->get(),
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

        // Send notification to owners
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
}
