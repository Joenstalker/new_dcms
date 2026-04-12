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
use App\Models\User;
use App\Mail\TenantApproved;
use App\Mail\TenantRejected;
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

            // Enhancement fields for UI toggles
            $reg->reminder_enabled = $reg->isReminderEnabled();
            $reg->auto_approve_enabled = $reg->isAutoApproveEnabled();

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
     * Approve a pending registration - tenant already exists from payment success.
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

        try {
            DB::beginTransaction();

            // Find existing tenant (created during payment success)
            $tenant = Tenant::where('id', $pendingRegistration->subdomain)->first();

            if (!$tenant) {
                // Tenant doesn't exist, create it (fallback for old registrations)
                $tenantId = $pendingRegistration->subdomain;

                $tenant = Tenant::create([
                    'id' => $tenantId,
                    'name' => $pendingRegistration->clinic_name,
                    'owner_name' => $pendingRegistration->first_name . ' ' . $pendingRegistration->last_name,
                    'status' => 'active',
                    'enabled_features' => \App\Models\Tenant::getDefaultFeatures(),
                    'email' => $pendingRegistration->email,
                    'phone' => $pendingRegistration->phone,
                    'street' => $pendingRegistration->street,
                    'region' => $pendingRegistration->region,
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
                $user = User::create([
                    'name' => $pendingRegistration->first_name . ' ' . $pendingRegistration->last_name,
                    'email' => $pendingRegistration->email,
                    'password' => $pendingRegistration->password,
                    'requires_password_change' => true,
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
            }
            else {
                // Tenant exists (created during payment) - now create tenant database and user
                // Initialize tenancy to create database and user
                tenancy()->initialize($tenant);

                // Create admin user in tenant database
                $user = User::create([
                    'name' => $pendingRegistration->first_name . ' ' . $pendingRegistration->last_name,
                    'email' => $pendingRegistration->email,
                    'password' => $pendingRegistration->password,
                    'requires_password_change' => true,
                ]);

                // Assign Owner role
                $user->assignRole('Owner');

                // End tenancy
                tenancy()->end();

                // Now update status to active
                $tenant->update([
                    'status' => 'active',
                    'enabled_features' => $tenant->enabled_features ?? \App\Models\Tenant::getDefaultFeatures(),
                    'landing_page_config' => Tenant::mergeLandingPageConfig(
                        is_array($tenant->landing_page_config) ? $tenant->landing_page_config : null
                    ),
                ]);
            }

            // Update pending registration status
            $pendingRegistration->update([
                'status' => PendingRegistration::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

            DB::commit();

            // Send approval email
            try {
                $tenantUrl = Tenant::publicWebsiteUrlForSubdomain($pendingRegistration->subdomain);
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
                'status' => $refunded ? PendingRegistration::STATUS_REFUNDED : PendingRegistration::STATUS_REJECTED,
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
     * Extend the pending time for a registration.
     */
    public function extendTime(Request $request, PendingRegistration $pendingRegistration)
    {
        $validated = $request->validate([
            'hours' => 'required|integer|min:1|max:720', // Max 30 days
        ]);

        if ($pendingRegistration->status !== PendingRegistration::STATUS_PENDING) {
            return response()->json(['message' => 'This registration has already been processed.'], 422);
        }

        try {
            $pendingRegistration->extendTime($validated['hours']);

            return response()->json(['message' => 'Pending time extended by ' . $validated['hours'] . ' hour(s).']);
        }
        catch (\Exception $e) {
            Log::error('Failed to extend pending time: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to extend pending time. Please try again.'], 500);
        }
    }

    /**
     * Set a specific expiry time for a registration.
     */
    public function setTime(Request $request, PendingRegistration $pendingRegistration): RedirectResponse
    {
        $validated = $request->validate([
            'expires_at' => 'required|date|after:now',
        ]);

        if ($pendingRegistration->status !== PendingRegistration::STATUS_PENDING) {
            return back()->with('error', 'This registration has already been processed.');
        }

        try {
            $newExpiry = Carbon::parse($validated['expires_at']);
            $pendingRegistration->setExpiryTime($newExpiry);

            return back()->with('success', 'Expiry time set to ' . $newExpiry->format('F j, Y g:i A'));
        }
        catch (\Exception $e) {
            Log::error('Failed to set expiry time: ' . $e->getMessage());
            return back()->with('error', 'Failed to set expiry time. Please try again.');
        }
    }

    /**
     * Toggle reminder enabled for a registration.
     */
    public function toggleReminder(Request $request, PendingRegistration $pendingRegistration)
    {
        if ($pendingRegistration->status !== PendingRegistration::STATUS_PENDING) {
            return response()->json(['message' => 'This registration has already been processed.'], 422);
        }

        try {
            $newValue = !$pendingRegistration->reminder_enabled;
            $pendingRegistration->update(['reminder_enabled' => $newValue]);

            $status = $newValue ? 'enabled' : 'disabled';
            return response()->json(['message' => 'Reminder ' . $status . ' for this registration.']);
        }
        catch (\Exception $e) {
            Log::error('Failed to toggle reminder: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to toggle reminder. Please try again.'], 500);
        }
    }

    /**
     * Toggle auto-approve enabled for a registration.
     */
    public function toggleAutoApprove(Request $request, PendingRegistration $pendingRegistration)
    {
        if ($pendingRegistration->status !== PendingRegistration::STATUS_PENDING) {
            return response()->json(['message' => 'This registration has already been processed.'], 422);
        }

        try {
            $newValue = !$pendingRegistration->auto_approve_enabled;
            $pendingRegistration->update(['auto_approve_enabled' => $newValue]);

            $status = $newValue ? 'enabled' : 'disabled';
            return response()->json(['message' => 'Auto-approve ' . $status . ' for this registration.']);
        }
        catch (\Exception $e) {
            Log::error('Failed to toggle auto-approve: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to toggle auto-approve. Please try again.'], 500);
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
