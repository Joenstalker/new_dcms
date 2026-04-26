<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Treatment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $reportLevel = $this->getReportLevel();

        // === BASIC TIER (All plans) ===
        $dailyAppointments = Appointment::whereDate('appointment_date', $today)->count();

        $dailyIncome = Treatment::whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('amount_paid');
        $weeklyIncome = Treatment::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->sum('amount_paid');
        $monthlyIncome = Treatment::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount_paid');
        $unpaidBalance = Treatment::query()
            ->selectRaw('COALESCE(SUM(CASE WHEN total_amount_due > amount_paid THEN total_amount_due - amount_paid ELSE 0 END), 0) AS unpaid_balance')
            ->value('unpaid_balance');

        $totalPatients = Patient::count();

        $recentAppointments = Appointment::with('patient')
            ->orderBy('appointment_date', 'desc')
            ->take(5)
            ->get();

        $data = [
            'stats' => [
                'daily_appointments' => $dailyAppointments,
                'daily_income' => round((float) $dailyIncome, 2),
                'weekly_income' => round((float) $weeklyIncome, 2),
                'monthly_income' => round((float) $monthlyIncome, 2),
                'monthly_revenue' => round((float) $monthlyIncome, 2),
                'unpaid_balance' => round((float) $unpaidBalance, 2),
                'total_patients' => $totalPatients,
            ],
            'recent_appointments' => $recentAppointments,
            'report_level' => $reportLevel,
            'can_export' => true,
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
        }

        return Inertia::render('Tenant/Reports/Index', $data);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'filter' => 'required|in:today,week,month,year,custom',
            'start_date' => 'nullable|date|required_if:filter,custom',
            'end_date' => 'nullable|date|required_if:filter,custom|after_or_equal:start_date',
        ]);

        $dateRange = $this->getDateRange(
            $validated['filter'],
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );
        $reportData = $this->buildPdfReportData($dateRange);
        $tenant = tenant();
        $logoSrc = $this->resolveReportLogoSrc($tenant);

        $digitalSignature = now()->format('YmdHis') . '-' . Str::upper(Str::random(4));

        $dompdfOptions = [
            'defaultFont' => 'Helvetica',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => false,
            'chroot' => base_path(),
            'dpi' => 96,
            'isFontSubsettingEnabled' => true,
        ];

        $pdf = Pdf::setOptions($dompdfOptions)->loadView('reports.export-pdf', [
            'tenant' => $tenant,
            'logoSrc' => $logoSrc,
            'dateRange' => $dateRange,
            'patients' => $reportData['patients'],
            'patientTotal' => $reportData['patient_total'],
            'appointments' => $reportData['appointments'],
            'appointmentTotal' => $reportData['appointment_total'],
            'income' => $reportData['income'],
            'digitalSignature' => $digitalSignature,
        ])->setPaper('a4', 'landscape');

        $filename = 'Clinic_Report_' . strtoupper($validated['filter']) . '_' . now()->format('YmdHis') . '.pdf';
        $domPdf = $pdf->getDomPDF();
        $domPdf->render();
        $canvas = $domPdf->getCanvas();
        $fontMetrics = $domPdf->getFontMetrics();
        $font = $fontMetrics->getFont('Helvetica', 'normal');
        $canvas->page_text(752, 574, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 9, [0.29, 0.33, 0.39]);

        return response()->streamDownload(
            static function () use ($domPdf) {
                echo $domPdf->output();
            },
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    private function resolveReportLogoSrc($tenant): ?string
    {
        $row = DB::table('branding_settings')
            ->whereIn('key', ['logo_base64', 'logo base64'])
            ->whereNotNull('binary_value')
            ->orderByDesc('updated_at')
            ->first();

        if ($row && !empty($row->binary_value)) {
            $mime = $this->detectImageMimeType($row->binary_value);
            return 'data:' . $mime . ';base64,' . base64_encode($row->binary_value);
        }

        $logoPath = $tenant?->logo_path;
        if (!$logoPath) {
            return null;
        }

        if (is_string($logoPath) && str_starts_with($logoPath, 'data:image')) {
            return $logoPath;
        }

        $candidatePaths = [
            public_path($logoPath),
            storage_path('app/public/' . ltrim($logoPath, '/')),
        ];

        foreach ($candidatePaths as $path) {
            if (is_string($path) && file_exists($path)) {
                $binary = @file_get_contents($path);
                if ($binary !== false) {
                    $mime = $this->detectImageMimeType($binary);
                    return 'data:' . $mime . ';base64,' . base64_encode($binary);
                }
            }
        }

        return null;
    }

    private function detectImageMimeType(string $binary): string
    {
        $header = substr($binary, 0, 12);

        if (str_starts_with($header, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($header, "\x89PNG")) {
            return 'image/png';
        }
        if (str_starts_with($header, 'GIF87a') || str_starts_with($header, 'GIF89a')) {
            return 'image/gif';
        }
        if (str_starts_with($header, 'RIFF') && substr($binary, 8, 4) === 'WEBP') {
            return 'image/webp';
        }
        if (str_contains($binary, '<svg') || str_contains($binary, '<SVG')) {
            return 'image/svg+xml';
        }

        return 'image/png';
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

    private function buildPdfReportData(array $range): array
    {
        $patients = Patient::with(['appointments:id,patient_id,appointment_date'])
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->orderBy('created_at')
            ->get()
            ->map(function (Patient $patient) {
                $appointments = $patient->appointments->sortBy('appointment_date')->values();
                $firstVisit = $patient->first_visit_at
                    ? Carbon::parse($patient->first_visit_at)->format('Y-m-d')
                    : optional($appointments->first())->appointment_date?->format('Y-m-d') ?? 'N/A';
                $lastVisit = $patient->last_visit_time
                    ? Carbon::parse($patient->last_visit_time)->format('Y-m-d')
                    : optional($appointments->last())->appointment_date?->format('Y-m-d') ?? 'N/A';

                return [
                    'id' => $patient->id,
                    'name' => trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')),
                    'address' => $patient->address ?: 'N/A',
                    'mobile' => $patient->phone ?: 'N/A',
                    'first_visit' => $firstVisit,
                    'last_visit' => $lastVisit,
                    'balance' => (float) ($patient->balance ?? 0),
                ];
            })
            ->values()
            ->all();

        $appointments = Appointment::with('patient:id,first_name,last_name')
            ->whereBetween('appointment_date', [$range['start'], $range['end']])
            ->orderBy('appointment_date', 'asc')
            ->get()
            ->map(function (Appointment $appointment) {
                $patientName = $appointment->patient
                    ? trim(($appointment->patient->first_name ?? '') . ' ' . ($appointment->patient->last_name ?? ''))
                    : trim(($appointment->guest_first_name ?? '') . ' ' . ($appointment->guest_last_name ?? ''));

                return [
                    'date_time' => $appointment->appointment_date?->format('Y-m-d H:i') ?? 'N/A',
                    'queue_reference' => $appointment->booking_reference ?: 'N/A',
                    'patient_name' => $patientName ?: 'N/A',
                    'service' => $appointment->service ?: 'N/A',
                    'status' => strtoupper((string) $appointment->status),
                ];
            })
            ->values()
            ->all();

        $income = Treatment::whereBetween('created_at', [$range['start'], $range['end']])
            ->selectRaw('COALESCE(SUM(amount_paid), 0) as paid')
            ->selectRaw('COALESCE(SUM(CASE WHEN total_amount_due > amount_paid THEN total_amount_due - amount_paid ELSE 0 END), 0) as unpaid_balance')
            ->first();

        return [
            'patients' => $patients,
            'patient_total' => count($patients),
            'appointments' => $appointments,
            'appointment_total' => count($appointments),
            'income' => [
                'paid' => round((float) ($income->paid ?? 0), 2),
                'unpaid_balance' => round((float) ($income->unpaid_balance ?? 0), 2),
            ],
        ];
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
