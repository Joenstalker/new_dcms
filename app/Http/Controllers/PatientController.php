<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\TenantNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function index()
    {
        return Inertia::render('Tenant/Patients/Index', [
            'patients' => Patient::latest()->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Patients/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'address' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'operation_history' => 'nullable|string',
            'balance' => 'nullable|numeric',
            'last_visit_time' => 'nullable|date',
        ]);

        $patient = Patient::create($validated);

        // Send notification to owners about new patient
        $notificationService = app(TenantNotificationService::class);
        $notificationService->notifyPatientAdded(
            auth()->user(),
        [
            'id' => $patient->id,
            'name' => $patient->first_name . ' ' . $patient->last_name,
        ]
        );

        return redirect()->route('tenant.patients.index')->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient)
    {
        $patient->load('appointments', 'treatments', 'invoices');
        return Inertia::render('Tenant/Patients/Show', [
            'patient' => $patient
        ]);
    }

    public function edit(Patient $patient)
    {
        return Inertia::render('Tenant/Patients/Edit', [
            'patient' => $patient
        ]);
    }

    public function delete(Patient $patient)
    {
        return Inertia::render('Tenant/Patients/Delete', [
            'patient' => $patient
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'address' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'operation_history' => 'nullable|string',
            'balance' => 'nullable|numeric',
            'last_visit_time' => 'nullable|date',
        ]);

        $patient->update($validated);

        return redirect()->back()->with('success', 'Patient updated successfully.');
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        return redirect()->route('tenant.patients.index')->with('success', 'Patient deleted successfully.');
    }
}
