<?php

namespace App\Http\Controllers;

use App\Events\TenantServiceChanged;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('creator')->latest()->get();

        return Inertia::render('Tenant/Services/Index', [
            'services' => $services,
            'can' => [
                'create' => auth()->user()->can('create services'),
                'edit' => auth()->user()->can('edit services'),
                'delete' => auth()->user()->can('delete services'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        $service = Service::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        $this->broadcastServiceChange($service->load('creator'), 'created');

        return redirect()->back()->with('success', 'Service created successfully.');
    }

    public function show($serviceId)
    {
        $service = Service::with('creator')->findOrFail($serviceId);
        return response()->json($service);
    }

    public function update(Request $request, $serviceId)
    {
        $service = Service::findOrFail($serviceId);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        $service->update($validated);
        $this->broadcastServiceChange($service->fresh()->load('creator'), 'updated');

        return redirect()->back()->with('success', 'Service updated successfully.');
    }

    public function destroy($serviceId)
    {
        $service = Service::findOrFail($serviceId);

        $deletedPayload = [
            'id' => $service->id,
        ];

        $service->delete();
        $this->broadcastRawServiceChange('deleted', $deletedPayload);

        return redirect()->back()->with('success', 'Service deleted successfully.');
    }

    private function broadcastServiceChange(Service $service, string $action): void
    {
        if (!tenant()) {
            return;
        }

        $payload = [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'price' => $service->price,
            'created_by' => $service->created_by,
            'creator' => $service->creator ? [
                'id' => $service->creator->id,
                'name' => $service->creator->name,
            ] : null,
            'created_at' => optional($service->created_at)?->toISOString(),
            'updated_at' => optional($service->updated_at)?->toISOString(),
        ];

        $this->broadcastRawServiceChange($action, $payload);
    }

    private function broadcastRawServiceChange(string $action, array $servicePayload): void
    {
        if (!tenant()) {
            return;
        }

        broadcast(new TenantServiceChanged((string) tenant()->getTenantKey(), $action, $servicePayload));
    }
}
