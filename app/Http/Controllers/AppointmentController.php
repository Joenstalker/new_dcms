<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
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

        Appointment::create($validated);

        return redirect()->route('tenant.appointments.index')->with('success', 'Appointment scheduled successfully.');
    }

    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:scheduled,completed,cancelled,walk-in',
            'appointment_date' => 'sometimes|required|date',
        ]);

        $appointment->update($validated);

        return redirect()->back()->with('success', 'Appointment updated.');
    }
}
