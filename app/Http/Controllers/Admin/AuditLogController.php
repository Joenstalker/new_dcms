<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Display the audit log listing.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $action = $request->query('action');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = AuditLog::with('admin')
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('target_id', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($action) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->paginate(25)->withQueryString();

        // Distinct action types for filter dropdown
        $actionTypes = AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'actionTypes' => $actionTypes,
            'filters' => compact('search', 'action', 'dateFrom', 'dateTo'),
        ]);
    }
}
