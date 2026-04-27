<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;

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

    public function export(Request $request)
    {
        $validated = $request->validate([
            'filter' => 'required|in:today,week,month,year,custom',
            'start_date' => 'nullable|date|required_if:filter,custom',
            'end_date' => 'nullable|date|required_if:filter,custom|after_or_equal:start_date',
            'types' => 'required|array',
            'types.*' => 'in:tenants,subscriptions,features,support',
        ]);

        $dateRange = $this->getDateRange(
            $validated['filter'],
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $reportData = [];
        foreach ($validated['types'] as $type) {
            $reportData[$type] = $this->getReportData($type, $dateRange);
        }

        $digitalSignature = now()->format('YmdHis') . '-' . Str::upper(Str::random(4));

        $dompdfOptions = [
            'defaultFont' => 'Helvetica',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
            'chroot' => base_path(),
            'dpi' => 96,
        ];

        $pdf = Pdf::setOptions($dompdfOptions)->loadView('admin.analytics.export-pdf', [
            'dateRange' => $dateRange,
            'reportData' => $reportData,
            'digitalSignature' => $digitalSignature,
        ])->setPaper('a4', 'landscape');

        $filename = 'Platform_Analytics_Report_' . strtoupper($validated['filter']) . '_' . now()->format('YmdHis') . '.pdf';
        
        return $pdf->download($filename);
    }

    private function getDateRange(string $filter, ?string $startDate = null, ?string $endDate = null): array
    {
        $now = Carbon::now();

        return match ($filter) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Today (' . $now->format('Y-m-d') . ')',
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'label' => 'This Week (' . $now->copy()->startOfWeek()->format('Y-m-d') . ' to ' . $now->copy()->endOfWeek()->format('Y-m-d') . ')',
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => 'This Month (' . $now->format('F Y') . ')',
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'label' => 'This Year (' . $now->format('Y') . ')',
            ],
            'custom' => [
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay(),
                'label' => 'Custom (' . Carbon::parse($startDate)->format('Y-m-d') . ' to ' . Carbon::parse($endDate)->format('Y-m-d') . ')',
            ],
        };
    }

    private function getReportData(string $type, array $range): array
    {
        return match ($type) {
            'tenants' => $this->getTenantsReportData($range),
            'subscriptions' => $this->getSubscriptionsReportData($range),
            'features' => $this->getFeaturesReportData($range),
            'support' => $this->getSupportReportData($range),
        };
    }

    private function getTenantsReportData(array $range): array
    {
        $tenants = Tenant::with('subscription.plan')
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->latest()
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
            'total' => $tenants->count(),
            'list' => $tenants,
        ];
    }

    private function getSubscriptionsReportData(array $range): array
    {
        $subscriptions = Subscription::with(['tenant', 'plan'])
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->latest()
            ->get()
            ->map(fn($sub) => [
                'id' => $sub->id,
                'tenant' => $sub->tenant?->name ?? 'N/A',
                'plan' => $sub->plan?->name ?? 'N/A',
                'status' => $sub->stripe_status,
                'amount' => $sub->plan?->price_monthly ?? 0,
                'created_at' => $sub->created_at->format('Y-m-d'),
            ]);

        return [
            'total' => $subscriptions->count(),
            'total_revenue' => $subscriptions->sum('amount'),
            'list' => $subscriptions,
        ];
    }

    private function getFeaturesReportData(array $range): array
    {
        $features = Feature::whereBetween('created_at', [$range['start'], $range['end']])
            ->where('type', '!=', 'SYSTEM_VERSION')
            ->latest()
            ->get()
            ->map(fn($feature) => [
                'id' => $feature->id,
                'name' => $feature->name,
                'key' => $feature->key,
                'type' => $feature->type,
                'created_at' => $feature->created_at->format('Y-m-d'),
            ]);

        return [
            'total' => $features->count(),
            'list' => $features,
        ];
    }

    private function getSupportReportData(array $range): array
    {
        if (!DB::getSchemaBuilder()->hasTable('support_tickets')) {
            return ['total' => 0, 'list' => []];
        }

        $tickets = SupportTicket::with('tenant')
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->latest()
            ->get()
            ->map(fn($ticket) => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'tenant' => $ticket->tenant?->name ?? $ticket->tenant_id,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'category' => $ticket->category,
                'created_at' => $ticket->created_at->format('Y-m-d H:i'),
            ]);

        return [
            'total' => $tickets->count(),
            'list' => $tickets,
        ];
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
