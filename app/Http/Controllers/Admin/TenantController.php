<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\PendingRegistration;
use App\Mail\TenantApproved;
use App\Mail\TenantRejected;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\SubscriptionPlan;
use App\Services\TenantNotificationService;
use Stancl\Tenancy\Database\Models\Domain;
use Carbon\Carbon;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Tenant::with(['domains', 'subscriptions.plan'])->latest();

        if ($search) {
            $query->whereHas('domains', function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%");
            });
        }

        $plans = SubscriptionPlan::all();
        $tenants = $query->get()->filter(function ($tenant) use ($status) {
            if (!$status)
                return true;
            return $tenant->status === $status;
        })->map(function ($tenant) {
            $latestSubscription = $tenant->subscriptions->where('stripe_status', 'active')->last()
                ?? $tenant->subscriptions->last();
            
            $tenant->plan = $latestSubscription ? $latestSubscription->plan->name : null;
            $tenant->plan_id = $latestSubscription ? $latestSubscription->subscription_plan_id : null;
            $tenant->database_name = $tenant->getDatabaseName();
            
            // Extra details from database columns
            $tenant->email = $tenant->email ?? 'N/A';
            $tenant->phone = $tenant->phone ?? 'N/A';
            $tenant->full_address = implode(', ', array_filter([
                $tenant->street,
                $tenant->barangay,
                $tenant->city,
                $tenant->province
            ])) ?: 'N/A';

            // Tenant URL
            $primaryDomain = $tenant->domains->first()?->domain;
            $tenant->tenant_url = $primaryDomain ? $this->getTenantUrl($primaryDomain) : null;

            // Subscription details
            if ($latestSubscription) {
                $tenant->billing_cycle = $latestSubscription->billing_cycle;
                $tenant->payment_status = $latestSubscription->payment_status;
                $tenant->amount_paid = $latestSubscription->stripe_price;
                $tenant->payment_method = $latestSubscription->payment_method ?? 'N/A';
                
                // Days left
                $expiry = $latestSubscription->ends_at;
                if (!$expiry) {
                    $days = $latestSubscription->billing_cycle === 'yearly' ? 365 : 30;
                    $expiry = $latestSubscription->created_at->addDays($days);
                }
                $tenant->days_left = (int) max(0, ceil(Carbon::now()->diffInHours($expiry, false) / 24));
                
                // Storage
                $tenant->max_storage_mb = $latestSubscription->plan->max_storage_mb ?? 500;
                $tenant->storage_used_mb = $this->getTenantStorageUsage($tenant->database_name);
            } else {
                $tenant->days_left = 0;
                $tenant->storage_used_mb = 0;
                $tenant->max_storage_mb = 500;
                $tenant->payment_method = 'N/A';
            }

            return $tenant;
        })->values();

        // Manual pagination (simulating for simple collection mapping)
        // Usually you'd use a database query for status if it were a real column.
        $perPage = 10;
        $page = $request->query('page', 1);
        $paginatedTenants = new \Illuminate\Pagination\LengthAwarePaginator(
            $tenants->forPage($page, $perPage)->values(), // Re-index for JSON array encoding
            $tenants->count(),
            $perPage,
            $page,
        ['path' => $request->url(), 'query' => $request->query()]
            );

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $paginatedTenants,
            'plans' => $plans,
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }

    /**
     * Display the specified tenant details.
     */
    public function show(Tenant $tenant): Response
    {
        $tenant->load(['domains', 'subscriptions.plan']);

        // Append the latest active plan name to the tenant object for the Vue frontend
        $latestSubscription = $tenant->subscriptions->where('stripe_status', 'active')->last()
            ?? $tenant->subscriptions->last();

        $tenant->plan = $latestSubscription ? $latestSubscription->plan->name : null;

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => $tenant,
            // You can fetch more specific stats here later (users, patients) by connecting to the tenant DB briefly
        ]);
    }

    /**
     * Update the tenant's subscription status.
     */
    public function updateStatus(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,suspended,pending_payment,cancelled',
        ]);

        // Update the status column and the subscription_status in data
        $tenant->update([
            'status' => $validated['status'],
            'subscription_status' => $validated['status']
        ]);

        AuditLog::record(
            'tenant_status_updated',
            "Updated clinic '{$tenant->name}' status to {$validated['status']}.",
            'Tenant',
            $tenant->id,
            ['new_status' => $validated['status']]
        );

        return back()->with('success', 'Tenant status updated successfully.');
    }

    /**
     * Update tenant information.
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'status' => 'required|in:active,suspended,pending_payment',
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        DB::beginTransaction();
        try {
            // Update Tenant
            $tenant->update([
                'name' => $validated['name'],
                'owner_name' => $validated['owner_name'],
                'status' => $validated['status'],
                'subscription_status' => $validated['status'], // Keep in sync
                'email' => $request->email, // Also update email if provided
                'phone' => $request->phone,
                'street' => $request->street,
                'barangay' => $request->barangay,
                'city' => $request->city,
                'province' => $request->province,
            ]);

            // Update Subscription
            $latestSubscription = $tenant->subscriptions()->latest()->first();
            if ($latestSubscription && $latestSubscription->subscription_plan_id != $validated['plan_id']) {
                $latestSubscription->update([
                    'subscription_plan_id' => $validated['plan_id']
                ]);
            }

            // Log action
            AuditLog::record(
                'tenant_updated',
                "Updated clinic '{$tenant->name}' information.",
                'Tenant',
                $tenant->id,
                ['changes' => $validated]
            );

            // Notify Tenant
            try {
                tenancy()->initialize($tenant);
                $notificationService = app(TenantNotificationService::class);
                $notificationService->broadcastToTenant(
                    'system_update',
                    'Clinic Information Updated',
                    'An administrator has updated your clinic information or subscription status.',
                    ['updated_by' => 'Central Admin', 'status' => $validated['status']]
                );
                tenancy()->end();
            } catch (\Exception $e) {
                Log::warning("Failed to notify tenant {$tenant->id}: " . $e->getMessage());
                if (tenancy()->initialized) tenancy()->end();
            }

            DB::commit();
            return back()->with('success', 'Clinic updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update clinic: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified tenant from storage.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        try {
            $clinicName = $tenant->name;
            $tenantId = $tenant->id;

            // Log the action (must do before deletion since tenant record is gone)
            AuditLog::record(
                'tenant_deleted',
                "Deleted clinic '{$clinicName}' and all associated data.",
                'Tenant',
                $tenantId,
                ['clinic_name' => $clinicName]
            );

            // Stancl Tenancy automatically handles dropping the database 
            // via the TenantDeleted event listener in TenancyServiceProvider.
            // Note: DROP DATABASE causes an implicit commit in MySQL, so we don't use transactions here.
            $tenant->delete(); 

            return back()->with('success', 'Clinic and all associated data have been permanently deleted.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete clinic: ' . $e->getMessage());
        }
    }

    /**
     * Helper to get tenant URL.
     */
    private function getTenantUrl(string $subdomain): string
    {
        $appUrl = config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?? 'http';
        $host = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';
        $port = parse_url($appUrl, PHP_URL_PORT) ? ':' . parse_url($appUrl, PHP_URL_PORT) : '';

        return "{$scheme}://{$subdomain}.{$host}{$port}";
    }

    /**
     * Helper to get storage usage in MB.
     */
    private function getTenantStorageUsage(string $dbName): float
    {
        try {
            $result = DB::connection('central')
                ->select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                          FROM information_schema.TABLES 
                          WHERE table_schema = ?", [$dbName]);

            return (float) ($result[0]->size_mb ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
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

            // Update pending registration status
            $pendingRegistration->update([
                'status' => PendingRegistration::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

            AuditLog::record(
                'registration_approved',
                "Approved registration for clinic '{$pendingRegistration->clinic_name}'.",
                'PendingRegistration',
                $pendingRegistration->id,
                ['tenant_id' => $tenantId]
            );

            DB::commit();

            // Send approval email
            $tenantUrl = config('app.url') . '/tenant/' . $pendingRegistration->subdomain;
            Mail::to($pendingRegistration->email)->send(new TenantApproved($pendingRegistration, $tenantUrl));

            return back()->with('success', 'Registration approved successfully. The tenant has been notified.');
        }
        catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to approve registration: ' . $e->getMessage());
            return back()->with('error', 'Failed to approve registration. Please try again.');
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
            // Update pending registration status
            $pendingRegistration->update([
                'status' => PendingRegistration::STATUS_REJECTED,
                'rejected_at' => now(),
                'admin_rejection_message' => $validated['rejection_message'],
            ]);

            AuditLog::record(
                'registration_rejected',
                "Rejected registration for clinic '{$pendingRegistration->clinic_name}'.",
                'PendingRegistration',
                $pendingRegistration->id,
                ['reason' => $validated['rejection_message']]
            );

            // Process refund via Stripe if payment exists
            if ($pendingRegistration->stripe_payment_intent_id) {
                $this->processRefund($pendingRegistration);
            }

            // Send rejection email
            Mail::to($pendingRegistration->email)->send(
                new TenantRejected($pendingRegistration, $validated['rejection_message'])
            );

            return back()->with('success', 'Registration rejected successfully. The applicant has been notified.');
        }
        catch (\Exception $e) {
            \Log::error('Failed to reject registration: ' . $e->getMessage());
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

            // Update registration with refund status
            $pendingRegistration->update([
                'status' => PendingRegistration::STATUS_REFUNDED,
            ]);

            AuditLog::record(
                'payment_refunded',
                "Processed Stripe refund for rejected registration '{$pendingRegistration->clinic_name}'.",
                'PendingRegistration',
                $pendingRegistration->id,
                ['payment_intent' => $pendingRegistration->stripe_payment_intent_id]
            );

            return true;
        }
        catch (\Exception $e) {
            \Log::error('Failed to process refund: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Approve a pending tenant clinic setup.
     */
    public function approveTenant(Request $request, Tenant $tenant): RedirectResponse
    {
        if ($tenant->status !== 'pending') {
            return back()->with('error', 'This clinic is not pending approval.');
        }

        try {
            DB::beginTransaction();

            $tenant->update([
                'status' => 'active',
                'subscription_status' => 'active',
            ]);

            AuditLog::record(
                'tenant_approved',
                "Approved clinic '{$tenant->name}'.",
                'Tenant',
                $tenant->id,
                ['status' => 'active']
            );

            DB::commit();

            // Find matching pending registration to process email and create user
            $registration = PendingRegistration::where('subdomain', $tenant->id)->first();
            
            if ($registration) {
                // Ensure registration status is synced
                if ($registration->status !== PendingRegistration::STATUS_APPROVED) {
                    $registration->update([
                        'status' => PendingRegistration::STATUS_APPROVED,
                        'approved_at' => now(),
                    ]);
                }

                // Initialize tenancy to create the user in the tenant's database
                try {
                    tenancy()->initialize($tenant);
                    
                    // Check if user already exists
                    $user = \App\Models\User::where('email', $registration->email)->first();
                    
                    if (!$user) {
                        $user = \App\Models\User::create([
                            'name' => $registration->first_name . ' ' . $registration->last_name,
                            'email' => $registration->email,
                            'password' => Hash::make($registration->password),
                        ]);
                        
                        // Assign Owner role
                        $user->assignRole('Owner');
                    }
                    
                    tenancy()->end();
                } catch (\Exception $e) {
                    Log::error("Failed to create user for tenant {$tenant->id}: " . $e->getMessage());
                    if (tenancy()->initialized) tenancy()->end();
                }

                $appUrl = config('app.url');
                $parsed = parse_url($appUrl);
                $host = $parsed['host'] ?? str_replace(['http://', 'https://'], '', $appUrl);
                $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
                $tenantUrl = 'http://' . $tenant->id . '.' . $host . $port;
                
                Mail::to($tenant->email)->send(new TenantApproved($registration, $tenantUrl));
            }

            return back()->with('success', 'Clinic approved successfully. The owner has been notified.');
        }
        catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve clinic: ' . $e->getMessage());
            return back()->with('error', 'Failed to approve clinic. Please try again.');
        }
    }

    /**
     * Reject a pending tenant clinic setup.
     */
    public function rejectTenant(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_message' => 'nullable|string|max:1000',
        ]);

        if ($tenant->status !== 'pending') {
            return back()->with('error', 'This clinic is not pending approval.');
        }

        try {
            $registration = PendingRegistration::where('subdomain', $tenant->id)->first();

            // Refund if there is a payment
            if ($registration && $registration->stripe_payment_intent_id && $registration->status !== PendingRegistration::STATUS_REFUNDED) {
                $this->processRefund($registration);
            }

            if ($registration) {
                $registration->update([
                    'status' => PendingRegistration::STATUS_REJECTED,
                    'rejected_at' => now(),
                    'admin_rejection_message' => $validated['rejection_message'],
                ]);

                Mail::to($tenant->email)->send(
                    new TenantRejected($registration, $validated['rejection_message'])
                );
            }

            $clinicName = $tenant->name;
            
            // Log before deletion
            AuditLog::record(
                'tenant_rejected',
                "Rejected and deleted clinic '{$clinicName}'.",
                'Tenant',
                $tenant->id,
                ['reason' => $validated['rejection_message']]
            );

            // Stancl Tenancy automatically handles dropping the database
            // Note: DROP DATABASE causes an implicit commit in MySQL, so we don't use transactions here.
            $tenant->delete();

            return back()->with('success', 'Clinic rejected successfully. The applicant has been notified and any payment refunded.');
        }
        catch (\Exception $e) {
            Log::error('Failed to reject clinic: ' . $e->getMessage());
            return back()->with('error', 'Failed to reject clinic. Please try again.');
        }
    }
}
