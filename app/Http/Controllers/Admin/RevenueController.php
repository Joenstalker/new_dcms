<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RevenueController extends Controller
{
    public function index(Request $request): Response
    {
        $activeSubscriptions = Subscription::where('stripe_status', 'active')
            ->with('plan')
            ->get();

        $mrr = $activeSubscriptions->sum(function ($sub) {
            if (!$sub->plan) return 0;
            return $sub->billing_cycle === 'yearly'
                ? $sub->plan->price_yearly / 12
                : $sub->plan->price_monthly;
        });

        $arr = $mrr * 12;

        $planDistribution = Subscription::where('stripe_status', 'active')
            ->with('plan')
            ->get()
            ->groupBy(fn ($sub) => $sub->plan?->name ?? 'Unknown')
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $label = $month->format('M Y');
            $count = Subscription::where('stripe_status', 'active')
                ->whereDate('created_at', '<=', $month->endOfMonth())
                ->count();
            $avgPrice = $activeSubscriptions->avg(fn ($sub) => $sub->plan?->price_monthly ?? 0);
            $monthlyRevenue[] = [
                'month'   => $label,
                'revenue' => round($count * $avgPrice, 2),
                'count'   => $count,
            ];
        }

        $statusBreakdown = [
            'active'    => Subscription::where('stripe_status', 'active')->count(),
            'past_due'  => Subscription::where('stripe_status', 'past_due')->count(),
            'canceled'  => Subscription::where('stripe_status', 'canceled')->count(),
            'trialing'  => Subscription::where('stripe_status', 'trialing')->count(),
        ];

        $recentSubscriptions = Subscription::with(['plan', 'tenant.domains'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($sub) {
                return [
                    'id'            => $sub->id,
                    'tenant_id'     => $sub->tenant_id,
                    'domain'        => $sub->tenant?->domains?->first()?->domain ?? 'N/A',
                    'plan'          => $sub->plan?->name ?? 'Unknown',
                    'billing_cycle' => $sub->billing_cycle,
                    'stripe_status' => $sub->stripe_status,
                    'amount'        => $sub->stripe_price,
                    'created_at'    => $sub->created_at?->toDateString(),
                ];
            });

        $churnThisMonth = Subscription::where('stripe_status', 'canceled')
            ->whereMonth('updated_at', Carbon::now()->month)
            ->whereYear('updated_at', Carbon::now()->year)
            ->count();

        return Inertia::render('Admin/Revenue/Index', [
            'stats' => [
                'mrr'                  => round($mrr, 2),
                'arr'                  => round($arr, 2),
                'active_subscriptions' => $activeSubscriptions->count(),
                'churn_this_month'     => $churnThisMonth,
            ],
            'plan_distribution'    => $planDistribution,
            'monthly_revenue'      => $monthlyRevenue,
            'status_breakdown'     => $statusBreakdown,
            'recent_subscriptions'  => $recentSubscriptions,
        ]);
    }
}
