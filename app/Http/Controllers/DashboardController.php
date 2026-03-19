<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Concern;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        Log::info('Dashboard accessed', [
            'tenant' => tenant('id'),
            'connection' => DB::connection()->getName(),
            'database' => DB::connection()->getDatabaseName(),
            'host' => request()->getHost()
        ]);

        // Check if we are in a tenant context
        if (!tenant()) {
            return Inertia::render('Tenant/Dashboard', [
                'stats' => [
                    'daily_appointments' => 0,
                    'monthly_revenue' => 0,
                    'total_patients' => 0,
                    'pending_appointments' => 0,
                ]
            ]);
        }

        // Stats for the tenant clinic
        $stats = [
            'daily_appointments' => Appointment::whereDate('appointment_date', Carbon::today())->count(),
            'monthly_revenue' => Invoice::where('status', 'paid')
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('total_amount'),
            'total_patients' => Patient::count(),
            'pending_appointments' => Appointment::where('status', 'pending')->count(),
        ];

        return Inertia::render('Tenant/Dashboard', [
            'stats' => $stats,
            'concerns' => Concern::latest()->get(),
        ]);
    }
}
