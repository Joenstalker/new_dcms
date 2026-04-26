<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the activity logs.
     */
    public function index(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('view activity logs');

        $query = Activity::with('causer')->latest();

        // Optional filtering by event type or user
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['event', 'causer_id']),
            'users' => \App\Models\User::select('id', 'name', 'email')->get(),
        ]);
    }
}
