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

        $limit = $tenant->getPlanLimit("max_{$resource}");
        
        // If limit is not set or unlimited (null), proceed
        if ($limit === null) {
            return $next($request);
        }

        // Check the current count. Stancl Tenancy automatically scopes these queries to the current tenant connection.
        $currentCount = match ($resource) {
            'users' => User::count(),
            'patients' => Patient::count(),
            'appointments' => Appointment::count(), // Consider grouping this by month if appointments are monthly limited
            default => 0,
        };

        if ($currentCount >= $limit) {
            $message = "You have reached your plan limit for {$resource} ({$limit}). Please upgrade your subscription to add more.";
            
            if ($request->wantsJson() || $request->inertia()) {
                abort(403, $message);
            }
            
            return redirect()->back()->with('error', $message);
        }

        return $next($request);
    }
}
