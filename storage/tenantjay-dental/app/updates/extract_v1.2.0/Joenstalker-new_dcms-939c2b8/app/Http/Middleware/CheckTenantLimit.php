<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;

class CheckTenantLimit
{
    /**
     * Handle an incoming request.
     * Enforce numerical limits (e.g., max_users, max_patients) on POST/creation routes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $resource  The resource to limit (e.g. 'users', 'patients', 'appointments')
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $resource)
    {
        $tenant = tenant();
        
        // Skip check if not in a tenant context
        // Also skip check for non-creation requests (e.g., GET/PUT/DELETE are allowed to view or edit existing data)
        if (!$tenant || !$request->isMethod('POST')) {
            return $next($request);
        }

        // Use the new semantic helper methods from the Tenant model
        $canAddMore = match ($resource) {
            'patients' => $tenant->canAddMorePatients(),
            'users' => $tenant->canAddMoreUsers(),
            'appointments' => $tenant->canAddMoreAppointments(),
            default => true,
        };

        if (!$canAddMore) {
            $limit = $tenant->getPlanLimit("max_{$resource}");
            $message = "You have reached your plan limit for {$resource} ({$limit}). Please upgrade your subscription to add more.";
            
            if ($request->wantsJson() || $request->inertia()) {
                abort(403, $message);
            }
            
            return redirect()->back()->with('error', $message);
        }

        return $next($request);
    }
}
