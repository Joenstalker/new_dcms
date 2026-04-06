<?php

namespace App\Http\Controllers;

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

        Service::create($validated);

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

        return redirect()->back()->with('success', 'Service rejected.');
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->back()->with('success', 'Service deleted successfully.');
    }
}
