<?php

namespace App\Http\Controllers;

use App\Events\TenantTreatmentChanged;
use App\Models\Treatment;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TreatmentController extends Controller
{
    public function index()
    {
        Gate::authorize('view progress notes');

        $treatments = Treatment::with('patient', 'dentist')->latest()->get();
        $patients = Patient::select('id', 'first_name', 'last_name')->get();

        return Inertia::render('Tenant/Treatments/Index', [
            'treatments' => $treatments,
            'patients' => $patients,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create progress notes');

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'diagnosis' => 'required|string|max:255',
            'procedure' => 'required|string',
            'notes' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
        ]);

        $validated['dentist_id'] = auth()->id() ?? null;

        $treatment = Treatment::create($validated);
        $this->broadcastTreatmentChange($treatment->load(['patient', 'dentist']), 'created');

        return redirect()->back()->with('success', 'Treatment record added successfully.');
    }

    public function show(Treatment $treatment)
    {
        Gate::authorize('view progress notes');

        return response()->json($treatment->load('patient', 'dentist'));
    }

    public function update(Request $request, Treatment $treatment)
    {
        Gate::authorize('edit progress notes');

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'diagnosis' => 'required|string|max:255',
            'procedure' => 'required|string',
            'notes' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
        ]);

        $treatment->update($validated);
        $this->broadcastTreatmentChange($treatment->fresh()->load(['patient', 'dentist']), 'updated');

        return redirect()->back()->with('success', 'Treatment record updated successfully.');
    }

    public function destroy(Treatment $treatment)
    {
        Gate::authorize('delete progress notes');

        $deletedPayload = [
            'id' => $treatment->id,
        ];

        $treatment->delete();
        $this->broadcastRawTreatmentChange('deleted', $deletedPayload);

        return redirect()->back()->with('success', 'Treatment record deleted successfully.');
    }

    private function broadcastTreatmentChange(Treatment $treatment, string $action): void
    {
        if (!tenant()) {
            return;
        }

        $payload = [
            'id' => $treatment->id,
            'patient_id' => $treatment->patient_id,
            'appointment_id' => $treatment->appointment_id,
            'dentist_id' => $treatment->dentist_id,
            'diagnosis' => $treatment->diagnosis,
            'procedure' => $treatment->procedure,
            'notes' => $treatment->notes,
            'cost' => $treatment->cost,
            'created_at' => optional($treatment->created_at)?->toISOString(),
            'updated_at' => optional($treatment->updated_at)?->toISOString(),
            'patient' => $treatment->patient ? [
                'id' => $treatment->patient->id,
                'first_name' => $treatment->patient->first_name,
                'last_name' => $treatment->patient->last_name,
            ] : null,
            'dentist' => $treatment->dentist ? [
                'id' => $treatment->dentist->id,
                'name' => $treatment->dentist->name,
            ] : null,
        ];

        $this->broadcastRawTreatmentChange($action, $payload);
    }

    private function broadcastRawTreatmentChange(string $action, array $treatmentPayload): void
    {
        if (!tenant()) {
            return;
        }

        broadcast(new TenantTreatmentChanged((string) tenant()->getTenantKey(), $action, $treatmentPayload));
    }
}
