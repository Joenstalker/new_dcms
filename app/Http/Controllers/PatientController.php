<?php

namespace App\Http\Controllers;

use App\Events\TenantPatientChanged;
use App\Models\Patient;
use App\Services\TenantNotificationService;
use App\Services\TenantStorageUsageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::query();
        $hasPatientType = $this->patientColumnExists('patient_type');
        $hasTags = $this->patientColumnExists('tags');
        $hasFirstVisitAt = $this->patientColumnExists('first_visit_at');
        $hasLastRecallAt = $this->patientColumnExists('last_recall_at');
        $hasLastVisitTime = $this->patientColumnExists('last_visit_time');

        $search = trim((string) $request->input('search', ''));
        $type = $request->input('type');
        $year = $request->input('year');
        $tag = $request->input('tag');
        $sort = $request->input('sort', 'latest');

        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $visitDateColumns = [];
        if ($hasFirstVisitAt) {
            $visitDateColumns[] = 'first_visit_at';
        }
        if ($hasLastVisitTime) {
            $visitDateColumns[] = 'last_visit_time';
        }
        $visitDateColumns[] = 'created_at';

        $visitDateExpression = 'COALESCE(' . implode(', ', $visitDateColumns) . ')';
        $visitYearExpression = $driver === 'sqlite'
            ? "strftime('%Y', {$visitDateExpression})"
            : "YEAR({$visitDateExpression})";

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($hasPatientType && in_array($type, ['pedia', 'adult'], true)) {
            $query->where('patient_type', $type);
        }

        if (!empty($year) && is_numeric($year)) {
            $query->whereRaw($visitYearExpression . ' = ?', [(string) ((int) $year)]);
        }

        if ($hasTags && !empty($tag)) {
            if ($driver === 'sqlite') {
                $query->whereRaw('EXISTS (SELECT 1 FROM json_each(tags) WHERE json_each.value = ?)', [$tag]);
            } else {
                $query->whereJsonContains('tags', $tag);
            }
        }

        switch ($sort) {
            case 'name_asc':
                $query->orderBy('last_name')->orderBy('first_name');

                break;
            case 'name_desc':
                $query->orderByDesc('last_name')->orderByDesc('first_name');

                break;
            case 'first_visit_desc':
                if ($hasFirstVisitAt) {
                    $query->orderByDesc('first_visit_at')->orderByDesc('created_at');
                } else {
                    $query->latest();
                }

                break;
            case 'last_recall_desc':
                if ($hasLastRecallAt) {
                    $query->orderByDesc('last_recall_at')->orderByDesc('created_at');
                } else {
                    $query->latest();
                }

                break;
            case 'balance_desc':
                $query->orderByDesc('balance')->orderByDesc('created_at');

                break;
            default:
                $query->latest();
        }

        $patients = $query->paginate(20)->withQueryString();

        $availableYears = Patient::query()
            ->selectRaw($visitYearExpression . ' as filter_year')
            ->whereRaw($visitYearExpression . ' IS NOT NULL')
            ->distinct()
            ->orderByDesc('filter_year')
            ->pluck('filter_year')
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->values();

        $availableTags = $hasTags
            ? Patient::query()
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatMap(function ($tags) {
                    return is_array($tags) ? $tags : [];
                })
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->unique()
                ->sort()
                ->values()
            : collect();

        return Inertia::render('Tenant/Patients/Index', [
            'patients' => $patients,
            'filters' => $request->only(['search', 'type', 'year', 'tag', 'sort']),
            'total_patients' => Patient::count(),
            'filtered_total' => $patients->total(),
            'available_years' => $availableYears,
            'available_tags' => $availableTags,
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Patients/Create');
    }

    public function store(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => ['nullable', 'regex:/^\d{11}$/'],
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'address' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'operation_history' => 'nullable|string',
            'patient_type' => 'nullable|in:pedia,adult',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'first_visit_at' => 'nullable|date',
            'last_recall_at' => 'nullable|date',
            'balance' => 'nullable|numeric',
            'initial_balance' => 'nullable|numeric',
            'last_visit_time' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
        ];

        if (!$this->patientColumnExists('patient_type')) {
            unset($rules['patient_type']);
        }
        if (!$this->patientColumnExists('tags')) {
            unset($rules['tags'], $rules['tags.*']);
        }
        if (!$this->patientColumnExists('first_visit_at')) {
            unset($rules['first_visit_at']);
        }
        if (!$this->patientColumnExists('last_recall_at')) {
            unset($rules['last_recall_at']);
        }

        $validated = $request->validate($rules);

        if ($this->patientColumnExists('tags')) {
            $validated['tags'] = collect($validated['tags'] ?? [])
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (
            $this->patientColumnExists('patient_type')
            && empty($validated['patient_type'])
            && !empty($validated['date_of_birth'])
        ) {
            $validated['patient_type'] = Carbon::parse((string) $validated['date_of_birth'])->age < 18 ? 'pedia' : 'adult';
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $validated['photo_path'] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }

        $validated = $this->filterToExistingPatientColumns($validated);

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
                'connection' => $query->getModel()->getConnectionName() ?? config('database.default'),
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

        if (!$request->header('X-Inertia') && ($request->wantsJson() || $request->ajax())) {
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

        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => ['nullable', 'regex:/^\d{11}$/'],
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'address' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'operation_history' => 'nullable|string',
            'patient_type' => 'nullable|in:pedia,adult',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'first_visit_at' => 'nullable|date',
            'last_recall_at' => 'nullable|date',
            'balance' => 'nullable|numeric',
            'initial_balance' => 'nullable|numeric',
            'last_visit_time' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
        ];

        if (!$this->patientColumnExists('patient_type')) {
            unset($rules['patient_type']);
        }
        if (!$this->patientColumnExists('tags')) {
            unset($rules['tags'], $rules['tags.*']);
        }
        if (!$this->patientColumnExists('first_visit_at')) {
            unset($rules['first_visit_at']);
        }
        if (!$this->patientColumnExists('last_recall_at')) {
            unset($rules['last_recall_at']);
        }

        $validated = $request->validate($rules);

        if ($this->patientColumnExists('tags')) {
            $validated['tags'] = collect($validated['tags'] ?? [])
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (
            $this->patientColumnExists('patient_type')
            && empty($validated['patient_type'])
            && !empty($validated['date_of_birth'])
        ) {
            $validated['patient_type'] = Carbon::parse((string) $validated['date_of_birth'])->age < 18 ? 'pedia' : 'adult';
        }

        if ($request->hasFile('photo')) {
            // Delete old photo if exists and is a file path
            if ($patient->photo_path && !str_starts_with($patient->photo_path, 'data:image')) {
                app(TenantStorageUsageService::class)->recordDelete('public', (string) $patient->photo_path);
                Storage::Disk('public')->delete($patient->photo_path);
            }
            $file = $request->file('photo');
            $validated['photo_path'] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }

        $validated = $this->filterToExistingPatientColumns($validated);

        $patient->update($validated);

        // Keep balance consistent when initial balance or financial context changes.
        if ($this->patientColumnExists('balance') && $this->patientColumnExists('initial_balance')) {
            $patient->recalculateBalance();
            $patient->refresh();
        }
        
        // Broadcast changes (with error handling)
        $this->broadcastPatientChange($patient, 'updated');

        if (!$request->header('X-Inertia') && ($request->wantsJson() || $request->ajax())) {
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
            'address' => $patient->address,
            'patient_type' => $patient->patient_type,
            'tags' => $patient->tags,
            'first_visit_at' => $patient->first_visit_at,
            'last_recall_at' => $patient->last_recall_at,
            'last_visit_time' => $patient->last_visit_time,
            'balance' => $patient->balance,
            'initial_balance' => $patient->initial_balance,
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

    private function patientColumnExists(string $column): bool
    {
        return Schema::connection(DB::connection()->getName())->hasColumn('patients', $column);
    }

    private function filterToExistingPatientColumns(array $attributes): array
    {
        $availableColumns = Schema::connection(DB::connection()->getName())->getColumnListing('patients');

        return array_filter(
            $attributes,
            fn ($key) => in_array($key, $availableColumns, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
