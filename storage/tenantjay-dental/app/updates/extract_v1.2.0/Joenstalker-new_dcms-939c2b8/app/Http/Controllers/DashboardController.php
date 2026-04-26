<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Concern;
use App\Models\Treatment;
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
                    'daily_income' => 0,
                    'total_patients' => 0,
                    'pending_appointments' => 0,
                ]
            ]);
        }

        // Stats for the tenant clinic
        $tenant = tenant();
        $subscription = $tenant->subscription()->with('plan')->first();
        $maxStorageMb = $subscription?->plan?->max_storage_mb ?? 500;

        $stats = [
            'daily_appointments' => Appointment::whereDate('appointment_date', Carbon::today())->count(),
            'daily_income' => Treatment::whereDate('created_at', Carbon::today())
                ->sum('amount_paid'),
            'total_patients' => Patient::count(),
            'pending_appointments' => Appointment::where('status', 'pending')->count(),
            'storage_used_bytes' => (int) $tenant->storage_used_bytes,
            'max_storage_bytes' => $maxStorageMb * 1024 * 1024,
        ];

        return Inertia::render('Tenant/Dashboard', [
            'stats' => $stats,
            'concerns' => Concern::latest()->get(),
        ]);
    }
}
