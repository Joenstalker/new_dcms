<?php

namespace App\Http\Controllers\Tenant;

use App\Events\TenantMedicalRecordChanged;
use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use Database\Factories\MedicalRecordFactory;
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
            'defaultMedicalChecklist' => MedicalRecordFactory::defaultChecklist(),
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

    public function generateDefaults(Request $request)
    {
        $checklist = collect(MedicalRecordFactory::defaultChecklist())
            ->mapWithKeys(fn (array $item) => [$item['name'] => $item]);

        $validated = $request->validate([
            'names' => ['nullable', 'array'],
            'names.*' => ['string'],
        ]);

        $requestedNames = collect($validated['names'] ?? [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        $itemsToCreate = $requestedNames->isEmpty()
            ? $checklist->values()
            : $requestedNames
                ->filter(fn ($name) => $checklist->has($name))
                ->map(fn ($name) => $checklist->get($name));

        $createdCount = 0;
        foreach ($itemsToCreate as $item) {
            $medicalRecord = MedicalRecord::updateOrCreate(
                ['name' => $item['name']],
                [
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                    'sort_order' => $item['sort_order'] ?? 0,
                ]
            );

            $this->broadcastMedicalRecordChange($medicalRecord->fresh(), 'created');
            $createdCount++;
        }

        return redirect()->back()->with('success', "{$createdCount} medical record item(s) generated successfully.");
    }

    public function generateSamples(Request $request)
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $checklist = collect(MedicalRecordFactory::defaultChecklist())
            ->shuffle()
            ->take((int) $validated['count']);

        $createdCount = 0;
        foreach ($checklist as $item) {
            $medicalRecord = MedicalRecord::updateOrCreate(
                ['name' => $item['name']],
                [
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                    'sort_order' => $item['sort_order'] ?? 0,
                ]
            );

            $this->broadcastMedicalRecordChange($medicalRecord->fresh(), 'created');
            $createdCount++;
        }

        return redirect()->back()->with('success', "{$createdCount} medical record item(s) generated from factory list.");
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
