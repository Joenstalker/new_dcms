<?php

namespace App\Http\Controllers;

use App\Events\TenantPatientChanged;
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
        $patient->refresh();
        $this->broadcastPatientChange($patient, 'created');

        return redirect()->route('patients.index')->with('success', 'Patient created successfully.');
    }

    public function show(Request $request, $patientId)
    {
        // Debug: Log tenancy state
        \Log::info('PatientController::show START', [
            'patient_id' => $patientId,
            'has_tenant' => !!tenant(),
            'tenant_id' => tenant()?->id,
        ]);

        // Verify tenant context is active
        if (!tenant()) {
            abort(403, 'No tenant context');
        }

        $tenantId = tenant()->id;
        
        // Debug: Check available database connections
        $connections = array_keys(config('database.connections', []));
        \Log::info('Available connections', ['connections' => $connections]);

        // Try to query using the tenant context with global scope
        // The HasTenantScope trait should scope queries automatically
        try {
            $query = Patient::query();
            \Log::info('Query builder created', [
                'model' => Patient::class,
                'connection' => $query->getConnection()->getName(),
            ]);
            
            $patient = $query
                ->where('id', $patientId)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();
                
            \Log::info('Patient loaded successfully', [
                'patient_id' => $patient->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load patient', [
                'patient_id' => $patientId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            abort(500, 'Failed to load patient data');
        }

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

    public function update(Request $request, $patientId)
    {
        // Verify tenant context is active
        if (!tenant()) {
            abort(403, 'No tenant context');
        }

        $tenantId = tenant()->id;
        
        // Query with explicit tenant_id to prevent cross-tenant access
        $patient = Patient::query()
            ->where('id', $patientId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

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
        
        // Broadcast changes (with error handling)
        $this->broadcastPatientChange($patient, 'updated');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Patient updated successfully',
                'patient' => $patient->load('appointments', 'treatments', 'invoices')
            ]);
        }

        return redirect()->back()->with('success', 'Patient updated successfully.');
    }

    public function destroy($patientId)
    {
        // Verify tenant context is active
        if (!tenant()) {
            abort(403, 'No tenant context');
        }

        $tenantId = tenant()->id;
        
        // Query with explicit tenant_id to prevent cross-tenant access
        $patient = Patient::query()
            ->where('id', $patientId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $deletedPayload = [
            'id' => $patient->id,
        ];

        $patient->delete();
        $this->broadcastRawPatientChange('deleted', $deletedPayload);

        return redirect()->route('patients.index')->with('success', 'Patient deleted successfully.');
    }

    public function downloadPdf(Patient $patient)
    {
        $patient->load('appointments', 'treatments', 'invoices');
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('tenant.patients.pdf', compact('patient'));

        return $pdf->download('Patient-Record-' . str_pad((string)$patient->id, 6, '0', STR_PAD_LEFT) . '.pdf');
    }

    private function broadcastPatientChange(Patient $patient, string $action): void
    {
        if (!tenant()) {
            return;
        }

        $payload = [
            'id' => $patient->id,
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'last_visit_time' => $patient->last_visit_time,
            'balance' => $patient->balance,
            'photo_path' => $patient->photo_path,
            'photo_url' => $patient->photo_url ?? null,
        ];

        $this->broadcastRawPatientChange($action, $payload);
    }

    private function broadcastRawPatientChange(string $action, array $patientPayload): void
    {
        if (!tenant()) {
            return;
        }

        try {
            // Only broadcast if BROADCAST_DRIVER is not null
            if (config('broadcasting.default') && config('broadcasting.default') !== 'null') {
                broadcast(new TenantPatientChanged((string)tenant()->getTenantKey(), $action, $patientPayload));
            }
        } catch (\Throwable $e) {
            // Don't let broadcasting failures crash the request
            \Log::warning('Failed to broadcast patient change', [
                'action' => $action,
                'error' => $e->getMessage(),
                'tenant_id' => tenant()->id,
            ]);
        }
    }
}
