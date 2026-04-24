<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentHistory;
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

        $query = Subscription::with(['plan', 'tenant.domains', 'latestPaymentHistory'])->latest();

        if ($search) {
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%");
            })->orWhereHas('tenant.domains', function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(15)->withQueryString();

        $historyQuery = PaymentHistory::query()->with(['subscription.plan', 'subscription.tenant.domains'])->latest('paid_at');

        if ($search) {
            $historyQuery->where(function ($q) use ($search) {
                $q->where('tenant_id', 'like', "%{$search}%")
                    ->orWhere('transaction_code', 'like', "%{$search}%")
                    ->orWhereHas('subscription.tenant.domains', function ($domainQuery) use ($search) {
                        $domainQuery->where('domain', 'like', "%{$search}%");
                    });
            });
        }

        $paymentHistories = $historyQuery->paginate(15, ['*'], 'history_page')->withQueryString();

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'paymentHistories' => $paymentHistories,
            'filters' => ['search' => $search],
        ]);
    }
}
