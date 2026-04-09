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
        $services = Service::with('creator', 'approver')->latest()->get();
        return Inertia::render('Tenant/Services/Index', [
            'services' => $services,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        $authenticatedUser = auth()->user();
        $isOwner = $authenticatedUser->hasRole('Owner');

        $validated['created_by'] = $authenticatedUser->id;
        $validated['status'] = $isOwner ? 'approved' : 'pending';

        if ($isOwner) {
            $validated['approved_by'] = $authenticatedUser->id;
        }

        $service = Service::create($validated);
        $this->broadcastServiceChange($service->load(['creator', 'approver']), 'created');

        $message = $validated['status'] === 'approved'
            ? 'Service added and approved successfully.'
            : 'Service submitted for approval.';

        return redirect()->back()->with('success', $message);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        $authenticatedUser = auth()->user();
        $isOwner = $authenticatedUser->hasRole('Owner');

        // If staff (Dentist/Assistant) updates, it goes back to pending
        if (!$isOwner) {
            $validated['status'] = 'pending';
            $validated['approved_by'] = null;
        }

        $service->update($validated);
        $this->broadcastServiceChange($service->fresh()->load(['creator', 'approver']), 'updated');

        $message = $service->status === 'approved'
            ? 'Service updated successfully.'
            : 'Service updated and resubmitted for approval.';

        return redirect()->back()->with('success', $message);
    }

    public function approve(Service $service)
    {
        if (!auth()->user()->hasRole('Owner')) {
            abort(403);
        }

        $service->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        $this->broadcastServiceChange($service->fresh()->load(['creator', 'approver']), 'approved');

        return redirect()->back()->with('success', 'Service approved successfully.');
    }

    public function reject(Service $service)
    {
        if (!auth()->user()->hasRole('Owner')) {
            abort(403);
        }

        $service->update([
            'status' => 'rejected',
        ]);

        $this->broadcastServiceChange($service->fresh()->load(['creator', 'approver']), 'rejected');

        return redirect()->back()->with('success', 'Service rejected.');
    }

    public function destroy(Service $service)
    {
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
            'status' => $service->status,
            'created_by' => $service->created_by,
            'approved_by' => $service->approved_by,
            'creator' => $service->creator ? [
                'id' => $service->creator->id,
                'name' => $service->creator->name,
            ] : null,
            'approver' => $service->approver ? [
                'id' => $service->approver->id,
                'name' => $service->approver->name,
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
