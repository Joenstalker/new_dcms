<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $reportLevel = $this->getReportLevel();

        // === BASIC TIER (All plans) ===
        $dailyAppointments = Appointment::whereDate('appointment_date', $today)->count();

        // Sum all payments received this month across all invoices (handles partial payments)
        $monthlyRevenue = Payment::whereBetween('created_at', [$startOfMonth, Carbon::now()])
            ->sum('amount');
        $totalPatients = Patient::count();

        $recentAppointments = Appointment::with('patient')
            ->orderBy('appointment_date', 'desc')
            ->take(5)
            ->get();

        $data = [
            'stats' => [
                'daily_appointments' => $dailyAppointments,
                'monthly_revenue' => $monthlyRevenue,
                'total_patients' => $totalPatients,
            ],
            'recent_appointments' => $recentAppointments,
            'report_level' => $reportLevel,
        ];

        // === ENHANCED TIER (Pro plans) ===
        if (in_array($reportLevel, ['enhanced', 'advanced'])) {
            $data['trends'] = $this->getTrendData();
            $data['appointment_breakdown'] = $this->getAppointmentBreakdown();
        }

        // === ADVANCED TIER (Ultimate plans) ===
        if ($reportLevel === 'advanced') {
            $data['service_breakdown'] = $this->getServiceBreakdown();
            $data['patient_growth'] = $this->getPatientGrowth();
            $data['can_export'] = true;
        }

        return Inertia::render('Tenant/Reports/Index', $data);
    }

    /**
     * Export reports as CSV (advanced tier only).
     */
    public function export(Request $request, string $format = 'csv')
    {
        if ($this->getReportLevel() !== 'advanced') {
            return back()->with('error', 'Export is available on the Ultimate plan only.');
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $invoices = Invoice::where('status', 'paid')
            ->whereBetween('created_at', [$startOfMonth, Carbon::now()])
            ->with('patient')
            ->get();

        if ($format === 'csv') {
            $csv = "Date,Patient,Amount,Status\n";
            foreach ($invoices as $invoice) {
                $patientName = $invoice->patient
                    ? $invoice->patient->first_name . ' ' . $invoice->patient->last_name
                    : 'N/A';
                $csv .= "{$invoice->created_at->format('Y-m-d')},{$patientName},{$invoice->amount_paid},{$invoice->status}\n";
            }

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="revenue-report-' . now()->format('Y-m-d') . '.csv"');
        }

        return back()->with('error', 'Unsupported export format.');
    }

    /**
     * Get the current tenant's report level from subscription.
     */
    private function getReportLevel(): string
    {
        $tenant = tenant();
        if (!$tenant)
            return 'basic';

        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        if (!$subscription || !$subscription->plan)
            return 'basic';

        return $subscription->plan->getFeatureValue('report_level') ?? 'basic';
    }

    /**
     * Monthly revenue & appointment trends for the last 6 months.
     */
    private function getTrendData(): array
    {
        $trends = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $revenue = Payment::whereBetween('created_at', [$start, $end])
                ->sum('amount');

            $appointments = Appointment::whereBetween('appointment_date', [$start, $end])->count();
            $completed = Appointment::whereBetween('appointment_date', [$start, $end])
                ->where('status', 'completed')
                ->count();

            $trends[] = [
                'month' => $month->format('M Y'),
                'revenue' => round($revenue, 2),
                'appointments' => $appointments,
                'completed' => $completed,
            ];
        }

        return $trends;
    }

    /**
     * Appointment status breakdown for the current month.
     */
    private function getAppointmentBreakdown(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        return [
            'scheduled' => Appointment::where('status', 'scheduled')
            ->where('appointment_date', '>=', $startOfMonth)->count(),
            'completed' => Appointment::where('status', 'completed')
            ->where('appointment_date', '>=', $startOfMonth)->count(),
            'cancelled' => Appointment::where('status', 'cancelled')
            ->where('appointment_date', '>=', $startOfMonth)->count(),
            'walk_in' => Appointment::where('status', 'walk-in')
            ->where('appointment_date', '>=', $startOfMonth)->count(),
        ];
    }

    /**
     * Revenue breakdown by service (advanced tier).
     */
    private function getServiceBreakdown(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        return Appointment::where('status', 'completed')
            ->where('appointment_date', '>=', $startOfMonth)
            ->whereNotNull('service')
            ->selectRaw('service, COUNT(*) as count')
            ->groupBy('service')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
        'service' => $row->service,
        'count' => $row->count,
        ])
            ->toArray();
    }

    /**
     * Monthly patient registration growth for the last 6 months (advanced tier).
     */
    private function getPatientGrowth(): array
    {
        $growth = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $growth[] = [
                'month' => $month->format('M Y'),
                'new_patients' => Patient::whereBetween('created_at', [$start, $end])->count(),
                'total' => Patient::where('created_at', '<=', $end)->count(),
            ];
        }

        return $growth;
    }
}
