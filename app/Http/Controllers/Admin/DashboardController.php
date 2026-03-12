<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::all()->filter(fn ($t) => ($t->subscription_status ?? 'active') === 'active')->count();
        $suspendedTenants = Tenant::all()->filter(fn ($t) => ($t->subscription_status ?? '') === 'suspended')->count();
        $pendingTenants = Tenant::all()->filter(fn ($t) => ($t->subscription_status ?? '') === 'pending_payment')->count();

        // New clinics this month
        $newThisMonth = Tenant::where('created_at', '>=', now()->startOfMonth())->count();

        // Calculated Subscription Data (Phase 3)
        $activeSubsCount = \App\Models\Subscription::where('stripe_status', 'active')->count();
        $monthlyRevenue = \App\Models\Subscription::where('stripe_status', 'active')
            ->with('plan')
            ->get()
            ->sum(function ($sub) {
                return $sub->plan ? $sub->plan->price_monthly : 0;
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_clinics' => $totalTenants,
                'active_subscriptions' => $activeSubsCount ?: $activeTenants, // Fallback to active tenants if no sub records yet
                'suspended_clinics' => $suspendedTenants,
                'pending_clinics' => $pendingTenants,
                'new_this_month' => $newThisMonth,
                'monthly_revenue' => $monthlyRevenue,
            ],
        ]);
    }
}
