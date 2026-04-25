<?php

namespace App\Http\Controllers;

use App\Events\TenantTreatmentChanged;
use App\Models\Treatment;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TreatmentController extends Controller
{
    public function index()
    {
        $this->authorizeProgressNotes('view progress notes');

        $treatments = Treatment::with(['patient', 'dentist', 'service', 'nextVisitDentist'])->latest()->get();
        $patients = Patient::select('id', 'first_name', 'last_name')->get();
        $services = Service::select('id', 'name', 'price')->orderBy('name')->get();
        $dentists = User::role('Dentist')->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Tenant/Treatments/Index', [
            'treatments' => $treatments,
            'patients' => $patients,
            'services' => $services,
            'dentists' => $dentists,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeProgressNotes('create progress notes');

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'service_id' => 'nullable|exists:services,id',
            'dentist_id' => 'nullable|exists:users,id',
            'diagnosis' => 'nullable|string|max:255',
            'procedure' => 'nullable|string|max:2000',
            'notes' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'payment_account' => 'nullable|in:cash,credit_card',
            'discount' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'is_last_visit' => 'nullable|boolean',
            'linked_treatment_id' => 'nullable|exists:treatments,id',
            'commission_deductions' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'commission_net' => 'nullable|numeric|min:0',
            'commission_use_percentage' => 'nullable|boolean',
            'schedule_next_visit' => 'nullable|boolean',
            'next_visit_at' => 'required_if:schedule_next_visit,1|nullable|date',
            'next_visit_procedure' => 'required_if:schedule_next_visit,1|nullable|string|max:1000',
            'next_visit_dentist_id' => 'required_if:schedule_next_visit,1|nullable|exists:users,id',
            'next_visit_remarks' => 'nullable|string|max:2000',
        ]);

        $cost = (float) ($validated['cost'] ?? 0);
        $discount = (float) ($validated['discount'] ?? 0);
        $amountPaid = (float) ($validated['amount_paid'] ?? 0);
        $commissionDeductions = (float) ($validated['commission_deductions'] ?? 0);
        $commissionPercentage = (float) ($validated['commission_percentage'] ?? 0);
        $usePercentage = (bool) ($validated['commission_use_percentage'] ?? true);

        $totalAmountDue = max($cost - $discount, 0);
        $amountPaid = min(max($amountPaid, 0), $totalAmountDue);
        $commissionBase = max($totalAmountDue - $commissionDeductions, 0);
        $commissionNet = $usePercentage
            ? round(($commissionBase * $commissionPercentage) / 100, 2)
            : (float) ($validated['commission_net'] ?? 0);

        $payload = [
            'patient_id' => $validated['patient_id'],
            'appointment_id' => $validated['appointment_id'] ?? null,
            'service_id' => $validated['service_id'] ?? null,
            'dentist_id' => $validated['dentist_id'] ?? (auth()->id() ?? null),
            'diagnosis' => trim((string) ($validated['diagnosis'] ?? '')) ?: $this->deriveDiagnosis((string) ($validated['procedure'] ?? '')),
            'procedure' => $validated['procedure'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'cost' => $cost,
            'payment_account' => $validated['payment_account'] ?? null,
            'discount' => $discount,
            'total_amount_due' => $totalAmountDue,
            'amount_paid' => $amountPaid,
            'is_last_visit' => (bool) ($validated['is_last_visit'] ?? false),
            'linked_treatment_id' => $validated['linked_treatment_id'] ?? null,
            'commission_deductions' => $commissionDeductions,
            'commission_percentage' => $commissionPercentage,
            'commission_net' => max($commissionNet, 0),
            'commission_use_percentage' => $usePercentage,
            'schedule_next_visit' => (bool) ($validated['schedule_next_visit'] ?? false),
            'next_visit_at' => $validated['next_visit_at'] ?? null,
            'next_visit_procedure' => $validated['next_visit_procedure'] ?? null,
            'next_visit_dentist_id' => $validated['next_visit_dentist_id'] ?? null,
            'next_visit_remarks' => $validated['next_visit_remarks'] ?? null,
        ];

        $treatment = DB::transaction(function () use ($payload) {
            $record = Treatment::create($payload);

            if ($record->schedule_next_visit && $record->next_visit_at) {
                Appointment::create([
                    'patient_id' => $record->patient_id,
                    'dentist_id' => $record->next_visit_dentist_id,
                    'appointment_date' => $record->next_visit_at,
                    'service' => $record->next_visit_procedure,
                    'notes' => $record->next_visit_remarks,
                    'status' => 'scheduled',
                    'type' => 'recall',
                ]);
            }

            return $record;
        });

        $this->broadcastTreatmentChange($treatment->load(['patient', 'dentist']), 'created');

        return redirect()->back()->with('success', 'Treatment record added successfully.');
    }

    public function show(Treatment $treatment)
    {
        $this->authorizeProgressNotes('view progress notes');

        return response()->json($treatment->load('patient', 'dentist', 'service', 'nextVisitDentist'));
    }

    public function options()
    {
        $this->authorizeProgressNotes('view progress notes');

        return response()->json([
            'services' => Service::query()->select('id', 'name', 'price')->orderBy('name')->get(),
            'dentists' => User::role('Dentist')->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Treatment $treatment)
    {
        $this->authorizeProgressNotes('edit progress notes');

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'service_id' => 'nullable|exists:services,id',
            'dentist_id' => 'nullable|exists:users,id',
            'diagnosis' => 'nullable|string|max:255',
            'procedure' => 'nullable|string|max:2000',
            'notes' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'payment_account' => 'nullable|in:cash,credit_card',
            'discount' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'is_last_visit' => 'nullable|boolean',
            'linked_treatment_id' => 'nullable|exists:treatments,id',
            'commission_deductions' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'commission_net' => 'nullable|numeric|min:0',
            'commission_use_percentage' => 'nullable|boolean',
            'schedule_next_visit' => 'nullable|boolean',
            'next_visit_at' => 'nullable|date',
            'next_visit_procedure' => 'nullable|string|max:1000',
            'next_visit_dentist_id' => 'nullable|exists:users,id',
            'next_visit_remarks' => 'nullable|string|max:2000',
        ]);

        $cost = (float) ($validated['cost'] ?? 0);
        $discount = (float) ($validated['discount'] ?? 0);
        $amountPaid = (float) ($validated['amount_paid'] ?? 0);
        $commissionDeductions = (float) ($validated['commission_deductions'] ?? 0);
        $commissionPercentage = (float) ($validated['commission_percentage'] ?? 0);
        $usePercentage = (bool) ($validated['commission_use_percentage'] ?? true);
        $totalAmountDue = max($cost - $discount, 0);
        $amountPaid = min(max($amountPaid, 0), $totalAmountDue);
        $commissionBase = max($totalAmountDue - $commissionDeductions, 0);
        $commissionNet = $usePercentage
            ? round(($commissionBase * $commissionPercentage) / 100, 2)
            : (float) ($validated['commission_net'] ?? 0);

        $treatment->update([
            'patient_id' => $validated['patient_id'],
            'appointment_id' => $validated['appointment_id'] ?? null,
            'service_id' => $validated['service_id'] ?? null,
            'dentist_id' => $validated['dentist_id'] ?? (auth()->id() ?? null),
            'diagnosis' => trim((string) ($validated['diagnosis'] ?? '')) ?: $this->deriveDiagnosis((string) ($validated['procedure'] ?? '')),
            'procedure' => $validated['procedure'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'cost' => $cost,
            'payment_account' => $validated['payment_account'] ?? null,
            'discount' => $discount,
            'total_amount_due' => $totalAmountDue,
            'amount_paid' => $amountPaid,
            'is_last_visit' => (bool) ($validated['is_last_visit'] ?? false),
            'linked_treatment_id' => $validated['linked_treatment_id'] ?? null,
            'commission_deductions' => $commissionDeductions,
            'commission_percentage' => $commissionPercentage,
            'commission_net' => max($commissionNet, 0),
            'commission_use_percentage' => $usePercentage,
            'schedule_next_visit' => (bool) ($validated['schedule_next_visit'] ?? false),
            'next_visit_at' => $validated['next_visit_at'] ?? null,
            'next_visit_procedure' => $validated['next_visit_procedure'] ?? null,
            'next_visit_dentist_id' => $validated['next_visit_dentist_id'] ?? null,
            'next_visit_remarks' => $validated['next_visit_remarks'] ?? null,
        ]);
        $this->broadcastTreatmentChange($treatment->fresh()->load(['patient', 'dentist']), 'updated');

        return redirect()->back()->with('success', 'Treatment record updated successfully.');
    }

    public function destroy(Treatment $treatment)
    {
        $this->authorizeProgressNotes('delete progress notes');

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
            'service_id' => $treatment->service_id,
            'service' => $treatment->service ? [
                'id' => $treatment->service->id,
                'name' => $treatment->service->name,
            ] : null,
            'payment_account' => $treatment->payment_account,
            'discount' => $treatment->discount,
            'total_amount_due' => $treatment->total_amount_due,
            'amount_paid' => $treatment->amount_paid,
            'is_last_visit' => (bool) $treatment->is_last_visit,
            'linked_treatment_id' => $treatment->linked_treatment_id,
            'commission_deductions' => $treatment->commission_deductions,
            'commission_percentage' => $treatment->commission_percentage,
            'commission_net' => $treatment->commission_net,
            'commission_use_percentage' => (bool) $treatment->commission_use_percentage,
            'schedule_next_visit' => (bool) $treatment->schedule_next_visit,
            'next_visit_at' => optional($treatment->next_visit_at)?->toISOString(),
            'next_visit_procedure' => $treatment->next_visit_procedure,
            'next_visit_dentist_id' => $treatment->next_visit_dentist_id,
            'next_visit_remarks' => $treatment->next_visit_remarks,
            'next_visit_dentist' => $treatment->nextVisitDentist ? [
                'id' => $treatment->nextVisitDentist->id,
                'name' => $treatment->nextVisitDentist->name,
            ] : null,
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

    private function deriveDiagnosis(string $procedure): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $procedure));
        if ($normalized === '') {
            return 'Progress Note';
        }

        return substr($normalized, 0, 255);
    }

    private function authorizeProgressNotes(string $permission): void
    {
        $user = auth()->user();

        if ($user && method_exists($user, 'hasRole') && $user->hasRole('Owner')) {
            return;
        }

        Gate::authorize($permission);
    }
}
