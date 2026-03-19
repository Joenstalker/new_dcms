<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $dailyAppointments = Appointment::whereDate('appointment_date', $today)->count();
        $monthlyRevenue = Invoice::where('status', 'paid')
                                 ->whereBetween('created_at', [$startOfMonth, Carbon::now()])
                                 ->sum('amount_paid');
                                 
        $totalPatients = Patient::count();
        
        $recentAppointments = Appointment::with('patient')
                                         ->orderBy('appointment_date', 'desc')
                                         ->take(5)
                                         ->get();

        return Inertia::render('Tenant/Reports/Index', [
            'stats' => [
                'daily_appointments' => $dailyAppointments,
                'monthly_revenue' => $monthlyRevenue,
                'total_patients' => $totalPatients,
            ],
            'recent_appointments' => $recentAppointments
        ]);
    }
}
