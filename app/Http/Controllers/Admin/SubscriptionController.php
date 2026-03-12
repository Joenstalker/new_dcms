<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $query = Subscription::with(['plan', 'tenant.domains'])->latest();

        if ($search) {
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%");
            })->orWhereHas('tenant.domains', function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'filters' => ['search' => $search],
        ]);
    }
}
