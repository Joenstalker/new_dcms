<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;

/**
 * Advanced Analytics Controller (Ultimate plan only).
 * 
 * Provides deep analytics dashboards: revenue by period,
 * patient acquisition, appointment fill rate, top services,
 * and dentist utilization.
 */
class AnalyticsController extends Controller
{
    public function index()
    {
        return Inertia::render('Tenant/Analytics/Index', [
            'revenue_data' => $this->getRevenueData(),
            'patient_data' => $this->getPatientData(),
            'appointment_data' => $this->getAppointmentData(),
            'service_data' => $this->getServiceData(),
            'dentist_data' => $this->getDentistData(),
            'summary' => $this->getSummary(),
        ]);
    }

    private function getSummary(): array
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $currentRevenue = Invoice::where('status', 'paid')
            ->where('created_at', '>=', $thisMonth)
            ->sum('amount_paid');

        $previousRevenue = Invoice::where('status', 'paid')
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->sum('amount_paid');

        $currentPatients = Patient::where('created_at', '>=', $thisMonth)->count();
        $previousPatients = Patient::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        $currentAppointments = Appointment::where('appointment_date', '>=', $thisMonth)->count();
        $noShows = Appointment::where('status', 'cancelled')
            ->where('appointment_date', '>=', $thisMonth)->count();

        return [
            'revenue' => round($currentRevenue, 2),
            'revenue_change' => $previousRevenue > 0 
                ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1) 
                : 0,
            'new_patients' => $currentPatients,
            'patient_change' => $previousPatients > 0 
                ? round((($currentPatients - $previousPatients) / $previousPatients) * 100, 1) 
                : 0,
            'appointments' => $currentAppointments,
            'no_show_rate' => $currentAppointments > 0 
                ? round(($noShows / $currentAppointments) * 100, 1) 
                : 0,
            'total_patients' => Patient::count(),
        ];
    }

    private function getRevenueData(): array
    {
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $data[] = [
                'month' => $month->format('M'),
                'year' => $month->format('Y'),
                'revenue' => round(Invoice::where('status', 'paid')
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('amount_paid'), 2),
            ];
        }

        return $data;
    }

    private function getPatientData(): array
    {
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $data[] = [
                'month' => $month->format('M'),
                'new' => Patient::whereBetween('created_at', [$start, $end])->count(),
                'total' => Patient::where('created_at', '<=', $end)->count(),
            ];
        }

        return $data;
    }

    private function getAppointmentData(): array
    {
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $total = Appointment::whereBetween('appointment_date', [$start, $end])->count();
            $completed = Appointment::whereBetween('appointment_date', [$start, $end])
                ->where('status', 'completed')->count();
            $cancelled = Appointment::whereBetween('appointment_date', [$start, $end])
                ->where('status', 'cancelled')->count();

            $data[] = [
                'month' => $month->format('M'),
                'total' => $total,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'fill_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ];
        }

        return $data;
    }

    private function getServiceData(): array
    {
        return Appointment::where('status', 'completed')
            ->where('appointment_date', '>=', Carbon::now()->subMonths(3))
            ->whereNotNull('service')
            ->selectRaw('service, COUNT(*) as count')
            ->groupBy('service')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'name' => $row->service,
                'count' => $row->count,
            ])
            ->toArray();
    }

    private function getDentistData(): array
    {
        $dentists = User::role('Dentist')->get();
        $startOfMonth = Carbon::now()->startOfMonth();

        return $dentists->map(function ($dentist) use ($startOfMonth) {
            $total = Appointment::where('dentist_id', $dentist->id)
                ->where('appointment_date', '>=', $startOfMonth)->count();
            $completed = Appointment::where('dentist_id', $dentist->id)
                ->where('appointment_date', '>=', $startOfMonth)
                ->where('status', 'completed')->count();

            return [
                'name' => $dentist->name,
                'total_appointments' => $total,
                'completed' => $completed,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ];
        })->toArray();
    }
}
