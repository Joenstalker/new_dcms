<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function index()
    {
        return Inertia::render('Patients/Index', [
            'patients' => Patient::latest()->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Patients/Create');
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
        ]);

        Patient::create($validated);

        return redirect()->route('tenant.patients.index')->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient)
    {
        $patient->load('appointments', 'treatments', 'invoices');
        return Inertia::render('Patients/Show', [
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
        ]);

        $patient->update($validated);

        return redirect()->back()->with('success', 'Patient updated successfully.');
    }
}
