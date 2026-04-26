<?php

namespace App\Http\Controllers\Tenant;

use App\Events\TenantMedicalRecordChanged;
use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MedicalRecordController extends Controller
{
    public function index()
    {
        return Inertia::render('Tenant/MedicalRecords/Index', [
            'medicalRecords' => MedicalRecord::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:medical_records,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        MedicalRecord::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $medicalRecord = MedicalRecord::query()
            ->orderBy('name')
            ->where('name', $validated['name'])
            ->first();

        if ($medicalRecord) {
            $this->broadcastMedicalRecordChange($medicalRecord, 'created');
        }

        return redirect()->back()->with('success', 'Medical record item created successfully.');
    }

    public function show(MedicalRecord $medicalRecord)
    {
        return response()->json($medicalRecord);
    }

    public function update(Request $request, MedicalRecord $medicalRecord)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:medical_records,name,' . $medicalRecord->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $medicalRecord->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $this->broadcastMedicalRecordChange($medicalRecord->fresh(), 'updated');

        return redirect()->back()->with('success', 'Medical record item updated successfully.');
    }

    public function destroy(MedicalRecord $medicalRecord)
    {
        $deletedPayload = [
            'id' => $medicalRecord->id,
        ];

        $medicalRecord->delete();
        $this->broadcastRawMedicalRecordChange('deleted', $deletedPayload);

        return redirect()->back()->with('success', 'Medical record item deleted successfully.');
    }

    private function broadcastMedicalRecordChange(MedicalRecord $medicalRecord, string $action): void
    {
        if (!tenant()) {
            return;
        }

        $payload = [
            'id' => $medicalRecord->id,
            'name' => $medicalRecord->name,
            'description' => $medicalRecord->description,
            'is_active' => (bool) $medicalRecord->is_active,
            'created_at' => optional($medicalRecord->created_at)?->toISOString(),
            'updated_at' => optional($medicalRecord->updated_at)?->toISOString(),
        ];

        $this->broadcastRawMedicalRecordChange($action, $payload);
    }

    private function broadcastRawMedicalRecordChange(string $action, array $medicalRecordPayload): void
    {
        if (!tenant()) {
            return;
        }

        broadcast(new TenantMedicalRecordChanged((string) tenant()->getTenantKey(), $action, $medicalRecordPayload));
    }
}
