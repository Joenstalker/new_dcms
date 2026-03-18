<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PendingRegistration;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Mail\TenantApproved;
use App\Mail\TenantRejected;
use App\Mail\RegistrationRefunded;
use Stancl\Tenancy\Database\Models\Domain;
use Carbon\Carbon;

class PendingRegistrationController extends Controller
{
    /**
     * Display a listing of pending registrations.
     */
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = PendingRegistration::query()->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('clinic_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subdomain', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $pendingRegistrations = $query->get()->map(function ($reg) {
            // Get plan name
            $plan = SubscriptionPlan::find($reg->subscription_plan_id);
            $reg->plan_name = $plan ? $plan->name : 'Unknown Plan';

            // Format amount
            $reg->formatted_amount = '₱' . number_format($reg->amount_paid, 2);

            // Check if expired
            $reg->is_expired = $reg->isExpired();

            // Status badge
            $reg->status_badge = match ($reg->status) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'refunded' => 'info',
                    default => 'secondary',
                };

            return $reg;
        });

        // Manual pagination
        $perPage = 10;
        $page = $request->query('page', 1);
        $paginatedRegistrations = new \Illuminate\Pagination\LengthAwarePaginator(
            $pendingRegistrations->forPage($page, $perPage)->values(),
            $pendingRegistrations->count(),
            $perPage,
            $page,
        ['path' => $request->url(), 'query' => $request->query()]
            );

        // Statistics
        $stats = [
            'pending' => PendingRegistration::where('status', PendingRegistration::STATUS_PENDING)->count(),
            'approved' => PendingRegistration::where('status', PendingRegistration::STATUS_APPROVED)->count(),
            'rejected' => PendingRegistration::where('status', PendingRegistration::STATUS_REJECTED)->count(),
            'total' => PendingRegistration::count(),
        ];

        return Inertia::render('Admin/PendingRegistrations/Index', [
            'registrations' => $paginatedRegistrations,
            'stats' => $stats,
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }

    /**
     * Display the specified pending registration.
     */
    public function show(PendingRegistration $pendingRegistration): Response
    {
        // Get plan details
        $plan = SubscriptionPlan::find($pendingRegistration->subscription_plan_id);

        $pendingRegistration->plan_name = $plan ? $plan->name : 'Unknown Plan';
        $pendingRegistration->formatted_amount = '₱' . number_format($pendingRegistration->amount_paid, 2);
        $pendingRegistration->is_expired = $pendingRegistration->isExpired();

        return Inertia::render('Admin/PendingRegistrations/Show', [
            'registration' => $pendingRegistration,
        ]);
    }

    /**
     * Approve a pending registration and create the tenant.
     */
    public function approve(Request $request, PendingRegistration $pendingRegistration): RedirectResponse
    {
        // Check if already approved
        if ($pendingRegistration->status !== PendingRegistration::STATUS_PENDING) {
            return back()->with('error', 'This registration has already been processed.');
        }

        // Check if expired
        if ($pendingRegistration->isExpired()) {
            return back()->with('error', 'This registration has expired.');
        }

        // Check if subdomain already exists
        if (Domain::where('domain', $pendingRegistration->subdomain)->exists()) {
            return back()->with('error', 'This subdomain is already taken.');
        }

        try {
            DB::beginTransaction();

            // Create tenant using subdomain as ID
            $tenantId = $pendingRegistration->subdomain;

            // Create tenant
            $tenant = Tenant::create([
                'id' => $tenantId,
                'name' => $pendingRegistration->clinic_name,
                'owner_name' => $pendingRegistration->first_name . ' ' . $pendingRegistration->last_name,
                'status' => 'active',
                'email' => $pendingRegistration->email,
                'phone' => $pendingRegistration->phone,
                'street' => $pendingRegistration->street,
                'barangay' => $pendingRegistration->barangay,
                'city' => $pendingRegistration->city,
                'province' => $pendingRegistration->province,
            ]);

            // Create domain
            $domain = $tenant->domains()->create([
                'domain' => $pendingRegistration->subdomain,
            ]);

            // Update tenant with domain_id
            $tenant->update(['domain_id' => $domain->id]);

            // Initialize tenancy to create database and user
            tenancy()->initialize($tenant);

            // Create admin user in tenant database
            $user = \App\Models\User::create([
                'name' => $pendingRegistration->first_name . ' ' . $pendingRegistration->last_name,
                'email' => $pendingRegistration->email,
                'password' => Hash::make($pendingRegistration->password),
            ]);

            // Assign Owner role
            $user->assignRole('Owner');

            // Create subscription
            $subscription = $tenant->subscriptions()->create([
                'subscription_plan_id' => $pendingRegistration->subscription_plan_id,
                'stripe_status' => 'active',
                'stripe_price' => $pendingRegistration->amount_paid,
                'billing_cycle' => $pendingRegistration->billing_cycle,
                'payment_method' => 'card',
                'payment_status' => 'paid',
            ]);

            // End tenancy
            tenancy()->end();

            // Update pending registration status
            $pendingRegistration->update([
                'status' => PendingRegistration::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

            DB::commit();

            // Send approval email
            try {
                $tenantUrl = config('app.url') . '/tenant/' . $pendingRegistration->subdomain;
                Mail::to($pendingRegistration->email)->send(new TenantApproved($pendingRegistration, $tenantUrl));
            }
            catch (\Exception $e) {
                Log::warning('Failed to send approval email: ' . $e->getMessage());
            }

            return back()->with('success', 'Registration approved successfully. The tenant has been notified.');
        }
        catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve registration: ' . $e->getMessage());
            return back()->with('error', 'Failed to approve registration: ' . $e->getMessage());
        }
    }

    /**
     * Reject a pending registration.
     */
    public function reject(Request $request, PendingRegistration $pendingRegistration): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_message' => 'nullable|string|max:1000',
        ]);

        // Check if already processed
        if ($pendingRegistration->status !== PendingRegistration::STATUS_PENDING) {
            return back()->with('error', 'This registration has already been processed.');
        }

        try {
            // Process refund via Stripe if payment exists
            $refunded = false;
            if ($pendingRegistration->stripe_payment_intent_id) {
                $refunded = $this->processRefund($pendingRegistration);
            }

            // Update pending registration status
            $pendingRegistration->update([
                'status' => $refunded ?PendingRegistration::STATUS_REFUNDED : PendingRegistration::STATUS_REJECTED,
                'rejected_at' => now(),
                'admin_rejection_message' => $validated['rejection_message'],
            ]);

            // Send rejection email
            try {
                Mail::to($pendingRegistration->email)->send(
                    new TenantRejected($pendingRegistration, $validated['rejection_message'])
                );
            }
            catch (\Exception $e) {
                Log::warning('Failed to send rejection email: ' . $e->getMessage());
            }

            return back()->with('success', 'Registration rejected successfully. The applicant has been notified.');
        }
        catch (\Exception $e) {
            Log::error('Failed to reject registration: ' . $e->getMessage());
            return back()->with('error', 'Failed to reject registration. Please try again.');
        }
    }

    /**
     * Process refund via Stripe.
     */
    private function processRefund(PendingRegistration $pendingRegistration): bool
    {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

            $refund = $stripe->refunds->create([
                'payment_intent' => $pendingRegistration->stripe_payment_intent_id,
            ]);

            return true;
        }
        catch (\Exception $e) {
            Log::error('Failed to process refund: ' . $e->getMessage());
            return false;
        }
    }
}
