<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        return inertia('Admin/Analytics/Index', [
            'tenants' => $this->getTenantsAnalytics(),
            'subscriptions' => $this->getSubscriptionsAnalytics(),
            'features' => $this->getFeatureAnalytics(),
            'support' => $this->getSupportAnalytics(),
        ]);
    }

    private function getTenantsAnalytics()
    {
        $totalTenants = Tenant::count();
        
        $activeTenants = Tenant::whereHas('subscription', function ($query) {
            $query->where('stripe_status', 'active');
        })->count();

        $tenantsByMonth = Tenant::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $tenantsByPlan = Tenant::select('subscription_plans.name as plan_name', DB::raw('COUNT(tenants.id) as count'))
            ->join('subscriptions', 'tenants.id', '=', 'subscriptions.tenant_id')
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->groupBy('subscription_plans.name')
            ->get();

        $recentTenants = Tenant::with('subscription.plan')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domains->first()?->domain,
                'plan' => $tenant->subscription?->plan?->name ?? 'No Plan',
                'status' => $tenant->subscription?->stripe_status ?? 'N/A',
                'created_at' => $tenant->created_at->format('Y-m-d'),
            ]);

        return [
            'total' => $totalTenants,
            'active' => $activeTenants,
            'inactive' => $totalTenants - $activeTenants,
            'growth' => $tenantsByMonth,
            'by_plan' => $tenantsByPlan,
            'recent' => $recentTenants,
        ];
    }

    private function getSubscriptionsAnalytics()
    {
        $totalSubscriptions = Subscription::count();

        $byStatus = Subscription::select('stripe_status as status', DB::raw('COUNT(*) as count'))
            ->groupBy('stripe_status')
            ->get();

        $byPlan = Subscription::select('subscription_plans.name as plan_name', DB::raw('COUNT(*) as count'))
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->groupBy('subscription_plans.name')
            ->get();

        $mrr = Subscription::where('stripe_status', 'active')
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->select(
                DB::raw('DATE_FORMAT(subscriptions.created_at, "%Y-%m") as month'),
                DB::raw('SUM(subscription_plans.price_monthly) as mrr')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->take(12)
            ->get();

        $totalMrr = Subscription::where('stripe_status', 'active')
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price_monthly');

        $cancelledLastMonth = Subscription::where('stripe_status', 'canceled')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        $churnRate = $totalSubscriptions > 0 
            ? round(($cancelledLastMonth / $totalSubscriptions) * 100, 2) 
            : 0;

        $newSubscriptions = Subscription::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'total' => $totalSubscriptions,
            'by_status' => $byStatus,
            'by_plan' => $byPlan,
            'mrr' => $mrr,
            'total_mrr' => $totalMrr,
            'churn_rate' => $churnRate,
            'new_subscriptions' => $newSubscriptions,
        ];
    }

    private function getFeatureAnalytics()
    {
        // Helper to query active subscriptions with a specific dynamic feature enabled
        $getUsage = function(string $featureKey) {
            return Subscription::where('stripe_status', 'active')
                ->whereHas('plan.features', function($query) use ($featureKey) {
                    $query->where('key', $featureKey)
                        ->where(function($q) {
                            $q->where('plan_features.value_boolean', true)
                              ->orWhereNotNull('plan_features.value_numeric')
                              ->orWhereNotNull('plan_features.value_tier');
                        });
                })->count();
        };

        $featureUsage = [
            'sms' => $getUsage('sms_notifications'),
            'branding' => $getUsage('custom_branding'),
            'analytics' => $getUsage('advanced_analytics'),
            'qr_booking' => $getUsage('qr_booking'),
        ];

        // Get features per plan using the dynamic relationship
        $featureByPlan = SubscriptionPlan::with(['features' => function($q) {
            $q->whereIn('key', ['sms_notifications', 'custom_branding', 'advanced_analytics', 'qr_booking']);
        }])->get()->map(function($plan) {
            return [
                'name' => $plan->name,
                'has_sms' => $plan->hasFeature('sms_notifications'),
                'has_branding' => $plan->hasFeature('custom_branding'),
                'has_analytics' => $plan->hasFeature('advanced_analytics'),
                'has_qr_booking' => $plan->hasFeature('qr_booking'),
            ];
        });

        return [
            'usage' => $featureUsage,
            'by_plan' => $featureByPlan,
        ];
    }

    private function getSupportAnalytics()
    {
        if (!DB::getSchemaBuilder()->hasTable('support_tickets')) {
            return [
                'total' => 0,
                'open' => 0,
                'resolved' => 0,
                'by_status' => [],
                'by_priority' => [],
                'by_category' => [],
                'avg_response_time' => 0,
                'recent' => [],
            ];
        }

        $total = SupportTicket::count();
        $open = SupportTicket::whereIn('status', ['open', 'in_progress', 'pending'])->count();
        $resolved = SupportTicket::whereIn('status', ['resolved', 'closed'])->count();

        $byStatus = SupportTicket::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $byPriority = SupportTicket::select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get();

        $byCategory = SupportTicket::select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->get();

        $avgResponseTime = SupportTicket::whereNotNull('resolved_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours'))
            ->value('avg_hours') ?? 0;

        $recentTickets = SupportTicket::latest()
            ->take(10)
            ->get()
            ->map(fn($ticket) => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'tenant_id' => $ticket->tenant_id,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'category' => $ticket->category,
                'created_at' => $ticket->created_at->format('Y-m-d H:i'),
            ]);

        return [
            'total' => $total,
            'open' => $open,
            'resolved' => $resolved,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'by_category' => $byCategory,
            'avg_response_time' => round($avgResponseTime, 1),
            'recent' => $recentTickets,
        ];
    }
}
