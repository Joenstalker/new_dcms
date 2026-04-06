<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::all()->filter(fn($t) => ($t->subscription_status ?? 'active') === 'active')->count();
        $suspendedTenants = Tenant::all()->filter(fn($t) => ($t->subscription_status ?? '') === 'suspended')->count();
        $pendingTenants = Tenant::all()->filter(fn($t) => ($t->subscription_status ?? '') === 'pending_payment')->count();

        // New clinics this month
        $newThisMonth = Tenant::where('created_at', '>=', now()->startOfMonth())->count();

        // Calculated Subscription Data
        $activeSubsCount = Subscription::where('stripe_status', 'active')->count();
        $totalRevenue = \App\Models\SystemEarning::sum('amount');

        // 1. Recent Activity (Audit logs)
        $recentActivity = AuditLog::with('admin')
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function ($log) {
            return [
            'id' => $log->id,
            'action' => $log->action,
            'description' => $log->description,
            'admin_name' => $log->admin ? $log->admin->name : 'System',
            'date' => $log->created_at->diffForHumans(),
            'type' => $log->target_type,
            ];
        });

        // 2. Subscription Distribution (for Chart.js)
        $distribution = SubscriptionPlan::withCount(['subscriptions' => function ($query) {
            $query->where('stripe_status', 'active');
        }])
            ->get()
            ->map(function ($plan) {
            return [
            'name' => $plan->name,
            'count' => $plan->subscriptions_count,
            ];
        })
            ->filter(fn($p) => $p['count'] > 0)
            ->values();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_clinics' => $totalTenants,
                'active_subscriptions' => $activeSubsCount ?: $activeTenants,
                'suspended_clinics' => $suspendedTenants,
                'pending_clinics' => $pendingTenants,
                'new_this_month' => $newThisMonth,
                'total_revenue' => $totalRevenue,
            ],
            'recentActivity' => $recentActivity,
            'subscriptionDistribution' => $distribution,
        ]);
    }
}
