<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Tenant::with(['domains', 'subscriptions.plan'])->latest();

        if ($search) {
            $query->whereHas('domains', function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%");
            });
        }

        $tenants = $query->get()->filter(function ($tenant) use ($status) {
            if (!$status)
                return true;
            return ($tenant->subscription_status ?? 'active') === $status;
        })->map(function ($tenant) {
            $latestSubscription = $tenant->subscriptions->where('stripe_status', 'active')->last()
                ?? $tenant->subscriptions->last();
            $tenant->plan = $latestSubscription ? $latestSubscription->plan->name : null;
            return $tenant;
        })->values();

        // Manual pagination (simulating for simple collection mapping)
        // Usually you'd use a database query for status if it were a real column.
        $perPage = 10;
        $page = $request->query('page', 1);
        $paginatedTenants = new \Illuminate\Pagination\LengthAwarePaginator(
            $tenants->forPage($page, $perPage)->values(), // Re-index for JSON array encoding
            $tenants->count(),
            $perPage,
            $page,
        ['path' => $request->url(), 'query' => $request->query()]
            );

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $paginatedTenants,
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }

    /**
     * Display the specified tenant details.
     */
    public function show(Tenant $tenant): Response
    {
        $tenant->load(['domains', 'subscriptions.plan']);

        // Append the latest active plan name to the tenant object for the Vue frontend
        $latestSubscription = $tenant->subscriptions->where('stripe_status', 'active')->last()
            ?? $tenant->subscriptions->last();

        $tenant->plan = $latestSubscription ? $latestSubscription->plan->name : null;

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => $tenant,
            // You can fetch more specific stats here later (users, patients) by connecting to the tenant DB briefly
        ]);
    }

    /**
     * Update the tenant's subscription status.
     */
    public function updateStatus(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,suspended,pending_payment,cancelled',
        ]);

        // Stancl Tenancy models store unknown attributes in the JSON 'data' column
        $tenant->update([
            'subscription_status' => $validated['status']
        ]);

        return back()->with('success', 'Tenant status updated successfully.');
    }
}
