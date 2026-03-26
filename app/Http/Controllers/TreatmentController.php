<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TreatmentController extends Controller
{
    public function index()
    {
        $treatments = Treatment::with('patient', 'dentist')->latest()->get();
        $patients = Patient::select('id', 'first_name', 'last_name')->get();

        return Inertia::render('Tenant/Treatments/Index', [
            'treatments' => $treatments,
            'patients' => $patients,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'diagnosis' => 'required|string|max:255',
            'procedure' => 'required|string',
            'notes' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
        ]);

        $validated['dentist_id'] = auth()->id() ?? null;

        Treatment::create($validated);

        return redirect()->back()->with('success', 'Treatment record added successfully.');
    }

    public function show(Treatment $treatment)
    {
        return response()->json($treatment->load('patient', 'dentist'));
    }

    public function update(Request $request, Treatment $treatment)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'diagnosis' => 'required|string|max:255',
            'procedure' => 'required|string',
            'notes' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
        ]);

        $treatment->update($validated);

        return redirect()->back()->with('success', 'Treatment record updated successfully.');
    }

    public function destroy(Treatment $treatment)
    {
        $treatment->delete();

        return redirect()->back()->with('success', 'Treatment record deleted successfully.');
    }
}
