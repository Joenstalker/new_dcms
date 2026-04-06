<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\TenantNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Tenant/Patients/Index', [
            'patients' => $query->latest()->get(),
            'filters' => $request->only('search')
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
            'initial_balance' => 'nullable|numeric',
            'last_visit_time' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $validated['photo_path'] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }

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

        $patient->recalculateBalance();

        return redirect()->route('patients.index')->with('success', 'Patient created successfully.');
    }

    public function show(Request $request, Patient $patient)
    {
        $patient->load('appointments', 'treatments', 'invoices');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($patient);
        }

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
            'initial_balance' => 'nullable|numeric',
            'last_visit_time' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists and is a file path
            if ($patient->photo_path && !str_starts_with($patient->photo_path, 'data:image')) {
                Storage::Disk('public')->delete($patient->photo_path);
            }
            $file = $request->file('photo');
            $validated['photo_path'] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }

        $patient->update($validated);
        $patient->recalculateBalance();

        return redirect()->back()->with('success', 'Patient updated successfully.');
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        return redirect()->route('patients.index')->with('success', 'Patient deleted successfully.');
    }

    public function downloadPdf(Patient $patient)
    {
        $patient->load('appointments', 'treatments', 'invoices');
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('tenant.patients.pdf', compact('patient'));

        return $pdf->download('Patient-Record-' . str_pad((string)$patient->id, 6, '0', STR_PAD_LEFT) . '.pdf');
    }
}
