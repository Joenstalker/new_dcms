<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Mail\RegistrationPending;
use App\Models\PendingRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Stancl\Tenancy\Database\Models\Domain;
use Stripe\Checkout\Session as StripeSession;
use Stripe\StripeClient;

class RegistrationController extends Controller
{
    /**
     * Step 1: Validate account setup data (clinic name, admin name, email, password)
     */
    public function validateAccount(Request $request)
    {
        $validated = $request->validate([
            'clinic_name' => 'required|string|max:255|min:3',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'admin_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'street' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'password' => 'sometimes|required|string|min:8',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account details validated',
            'data' => $validated
        ]);
    }

    /**
     * Step 2: Check subdomain availability
     */
    public function checkSubdomain(Request $request)
    {
        $request->validate([
            'subdomain' => 'required|string|min:3|max:63|regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
        ]);

        $subdomain = strtolower($request->subdomain);

        // Check if subdomain is already taken
        $exists = Domain::where('domain', $subdomain)->exists();

        // Check reserved subdomains
        $reserved = ['www', 'admin', 'api', 'mail', 'ftp', 'localhost', 'dcms', 'app', 'blog', 'shop', 'support', 'help'];

        if (in_array($subdomain, $reserved)) {
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => 'This subdomain is reserved and cannot be used.',
            ]);
        }

        return response()->json([
            'success' => true,
            'available' => !$exists,
            'message' => $exists ? 'This subdomain is already taken.' : 'This subdomain is available.',
        ]);
    }

    /**
     * Step 3: Generate subdomain suggestions based on clinic name
     */
    public function suggestSubdomain(Request $request)
    {
        $request->validate([
            'clinic_name' => 'required|string|max:255',
        ]);

        $name = strtolower(trim($request->clinic_name));

        // Remove special characters and spaces
        $base = preg_replace('/[^a-z0-9]/', '', $name);

        $suggestions = [];
        $usedSubdomains = Domain::pluck('domain')->toArray();

        // Generate suggestions
        $variants = [
            $base,
            $base . 'clinic',
            $base . 'dental',
            $base . 'care',
            substr($base, 0, min(10, strlen($base))), // Shortened
        ];

        foreach ($variants as $variant) {
            if (strlen($variant) >= 3 && !in_array($variant, $usedSubdomains)) {
                $suggestions[] = $variant;
            }
            if (count($suggestions) >= 3)
                break;
        }

        // Add numeric variants if needed
        if (count($suggestions) < 3) {
            for ($i = 1; $i <= 5; $i++) {
                $variant = $base . $i;
                if (!in_array($variant, $usedSubdomains)) {
                    $suggestions[] = $variant;
                }
                if (count($suggestions) >= 3)
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'suggestions' => array_slice($suggestions, 0, 3),
        ]);
    }

    /**
     * Create checkout session for Stripe payment
     * First saves to PendingRegistration, then creates Stripe session
     */
    public function createCheckoutSession(Request $request)
    {
        $validated = $request->validate([
            'clinic_name' => 'required|string|max:255|min:3',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'admin_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:20',
            'street' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'password' => 'required|string|min:8',
            'subdomain' => 'required|string|max:63',
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        // Check if subdomain is already registered
        if (Domain::where('domain', strtolower($validated['subdomain']))->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This subdomain is already taken.',
            ], 400);
        }

        // Get the selected plan
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        // Determine price based on billing cycle
        $price = $validated['billing_cycle'] === 'yearly'
            ? $plan->price_yearly
            : $plan->price_monthly;

        // Determine Stripe price ID
        $stripePriceId = $validated['billing_cycle'] === 'yearly'
            ? $plan->stripe_yearly_price_id
            : $plan->stripe_monthly_price_id;

        if (!$stripePriceId) {
            return response()->json([
                'success' => false,
                'message' => 'Selected plan is not available for payment. Please contact support.',
            ], 400);
        }

        try {
            // Get configurable timeout from system settings
            $defaultTimeoutHours = \App\Models\SystemSetting::get('pending_timeout_default_hours', 168);

            // First, create or update a PendingRegistration record
            $pendingRegistration = PendingRegistration::updateOrCreate(
                ['subdomain' => strtolower($validated['subdomain'])],
                [
                    'clinic_name' => $validated['clinic_name'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'street' => $validated['street'],
                    'region' => $validated['region'],
                    'barangay' => $validated['barangay'],
                    'city' => $validated['city'],
                    'province' => $validated['province'],
                    'password' => $validated['password'],
                    'subscription_plan_id' => $validated['plan_id'],
                    'billing_cycle' => $validated['billing_cycle'],
                    'amount_paid' => $price,
                    'status' => PendingRegistration::STATUS_PENDING,
                    'verification_token' => PendingRegistration::generateToken(),
                    'expires_at' => now('UTC')->addHours($defaultTimeoutHours),
                    'pending_timeout_hours' => $defaultTimeoutHours,
                ]
            );

            $stripe = new StripeClient(config('services.stripe.secret'));

            // Create a metadata object to store registration data
            $metadata = [
                'pending_registration_id' => $pendingRegistration->id,
                'clinic_name' => $validated['clinic_name'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'admin_name' => $validated['admin_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'street' => $validated['street'],
                'region' => $validated['region'],
                'barangay' => $validated['barangay'],
                'city' => $validated['city'],
                'province' => $validated['province'],
                'password' => $validated['password'], // Will be hashed after payment
                'subdomain' => $validated['subdomain'],
                'plan_id' => $validated['plan_id'],
                'billing_cycle' => $validated['billing_cycle'],
            ];

            // Create Stripe checkout session (Hosted - redirects to Stripe page)
            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price' => $stripePriceId,
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'subscription',
                'success_url' => config('app.url') . '/registration/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.url') . '/?cancelled=true',
                'customer_email' => $validated['email'],
                'metadata' => $metadata,
            ]);

            // Update PendingRegistration with Stripe session ID
            $pendingRegistration->update([
                'stripe_session_id' => $session->id,
            ]);

            return response()->json([
                'success' => true,
                'url' => $session->url,
                'sessionId' => $session->id,
            ]);
        }
        catch (\Exception $e) {
            Log::error('Stripe checkout creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment session. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle successful payment - create tenant with pending status
     * Tenant will appear in Tenants table with 'pending' status for admin review
     */
    public function handleSuccess(Request $request)
    {
        $sessionId = $request->session_id;

        if (!$sessionId) {
            return view('errors.registration-failed', [
                'code' => '400',
                'title' => 'Session Error',
                'message' => 'No payment session found.'
            ]);
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return view('errors.registration-failed', [
                    'code' => '402',
                    'title' => 'Payment Incomplete',
                    'message' => 'Payment was not completed. Please try again.'
                ]);
            }

            $metadata = $session->metadata;
            $pendingRegistrationId = $metadata->pending_registration_id ?? null;

            if (!$pendingRegistrationId) {
                Log::error('No pending_registration_id found in Stripe metadata');
                return view('errors.registration-failed', [
                    'code' => '500',
                    'title' => 'Registration Error',
                    'message' => 'Registration record not found. Please contact support.'
                ]);
            }

            // Find the pending registration
            $pendingRegistration = PendingRegistration::find($pendingRegistrationId);

            if (!$pendingRegistration) {
                Log::error('Pending registration not found: ' . $pendingRegistrationId);
                return view('errors.registration-failed', [
                    'code' => '404',
                    'title' => 'Registration Not Found',
                    'message' => 'Your registration record could not be found.'
                ]);
            }

            // Check if already processed (tenant already created)
            if ($pendingRegistration->status !== PendingRegistration::STATUS_PENDING) {
                // Already processed, show appropriate message
                if ($pendingRegistration->status === PendingRegistration::STATUS_APPROVED) {
                    return redirect()->to('/?already-approved=true');
                }
                return redirect()->to('/?registration-status=' . $pendingRegistration->status);
            }

            // Create tenant record (but NOT the tenant database - waits for admin approval)
            DB::beginTransaction();

            try {
                $tenantId = strtolower(trim($pendingRegistration->subdomain));

                // Create tenant in central database only (no tenant DB yet)
                $tenant = Tenant::create([
                    'id' => $tenantId,
                    'name' => $pendingRegistration->clinic_name,
                    'owner_name' => $pendingRegistration->first_name . ' ' . $pendingRegistration->last_name,
                    'email' => $pendingRegistration->email,
                    'phone' => $pendingRegistration->phone,
                    'street' => $pendingRegistration->street,
                    'region' => $pendingRegistration->region,
                    'barangay' => $pendingRegistration->barangay,
                    'city' => $pendingRegistration->city,
                    'province' => $pendingRegistration->province,
                    'status' => 'pending', // Set to pending for admin review
                ]);

                // Create domain
                $domain = $tenant->domains()->create([
                    'domain' => $pendingRegistration->subdomain,
                ]);

                // Update tenant with domain_id
                $tenant->update(['domain_id' => $domain->id]);

                // NOTE: Tenant database and user will be created AFTER admin approval
                // This happens in PendingRegistrationController::approve()

                // Create subscription record (but tenant is still pending)
                // Note: subscription will be more complete after approval
                $subscription = $tenant->subscriptions()->create([
                    'subscription_plan_id' => $pendingRegistration->subscription_plan_id,
                    'stripe_id' => $session->subscription ?? null,
                    'stripe_status' => 'active',
                    'stripe_price' => $session->amount_total / 100,
                    'billing_cycle' => $pendingRegistration->billing_cycle,
                    'payment_method' => 'card',
                    'payment_status' => 'paid',
                ]);

                // Update pending registration status - keep as pending for admin review
                // The expires_at field controls when the pending status expires
                $pendingRegistration->update([
                    'stripe_session_id' => $session->id,
                    'stripe_payment_intent_id' => $session->payment_intent ?? null,
                    'amount_paid' => $session->amount_total / 100,
                    // Keep status as pending - admin needs to approve
                    'status' => PendingRegistration::STATUS_PENDING,
                ]);

                DB::commit();

                // Notify admins about new pending tenant awaiting review
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notifyAdmins(
                    'new_pending_tenant',
                    'New Tenant Pending Review',
                    "A new clinic '{$pendingRegistration->clinic_name}' has registered and payment received. Please review.",
                [
                    'tenant_id' => $tenantId,
                    'clinic_name' => $pendingRegistration->clinic_name,
                    'subdomain' => $pendingRegistration->subdomain,
                    'admin_email' => $pendingRegistration->email,
                    'amount_paid' => $pendingRegistration->amount_paid,
                ],
                    'both'
                );

                // Send pending registration email to client
                Mail::to($pendingRegistration->email)->send(new RegistrationPending($pendingRegistration));

                // Show success page - payment received and tenant created, awaiting review
                return view('emails.registration.payment-received', [
                    'registration' => $pendingRegistration,
                ]);
            }
            catch (\Exception $e) {
                DB::rollBack();
                Log::error('Tenant creation failed: ' . $e->getMessage());
                return view('errors.registration-failed', [
                    'code' => '500',
                    'title' => 'Registration Failed',
                    'message' => 'Failed to create your clinic. Please contact support.'
                ]);
            }
        }
        catch (\Exception $e) {
            Log::error('Payment success handling failed: ' . $e->getMessage());
            return view('errors.registration-failed', [
                'code' => '500',
                'title' => 'Verification Failed',
                'message' => 'Payment verification failed. Please contact support.'
            ]);
        }
    }

    /**
     * Webhook handler for Stripe events
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('stripe-signature');

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );

            // Handle the event
            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;
                    // Process completed payment if not already done
                    break;

                case 'customer.subscription.deleted':
                    // Handle subscription cancellation
                    break;

                default:
                    Log::info('Unhandled Stripe event: ' . $event->type);
            }

            return response()->json(['success' => true]);
        }
        catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 400);
        }
    }

    /**
     * Get plans for payment modal
     */
    public function getPlans()
    {
        $plans = SubscriptionPlan::orderBy('price_monthly')->get();

        return response()->json([
            'success' => true,
            'plans' => $plans->map(function ($plan) {
            return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price_monthly' => $plan->price_monthly,
                    'price_yearly' => $plan->price_yearly,
                    'stripe_monthly_price_id' => $plan->stripe_monthly_price_id,
                    'stripe_yearly_price_id' => $plan->stripe_yearly_price_id,
                    'max_users' => $plan->getFeatureValue('max_users'),
                    'max_patients' => $plan->getFeatureValue('max_patients'),
                    'features' => [
                        'qr_booking' => $plan->hasFeature('qr_booking'),
                        'sms' => $plan->hasFeature('sms_notifications'),
                        'branding' => $plan->hasFeature('custom_branding'),
                        'analytics' => $plan->hasFeature('advanced_analytics'),
                        'priority_support' => $plan->hasFeature('priority_support'),
                        'multi_branch' => $plan->hasFeature('multi_branch'),
                    ],
                ];
        }),
        ]);
    }

    /**
     * Fallback route for local development without wildcard DNS
     * Access tenant via /tenant/{subdomain}
     */
    public function accessTenant(Request $request, $subdomain)
    {
        // Find the tenant by domain
        $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $subdomain)->first();

        if (!$domain) {
            abort(404, 'Clinic not found');
        }

        // Initialize tenancy for this tenant
        $tenant = $domain->tenant;

        // Check if tenant is pending verification
        if ($tenant->status === 'pending') {
            // Find associated pending registration to get correct expiry time
            $pendingRegistration = \App\Models\PendingRegistration::where('subdomain', $subdomain)
                ->where('status', \App\Models\PendingRegistration::STATUS_PENDING)
                ->first();

            return response()->view('errors.pending', [
                'tenant' => $tenant,
                'created_at' => $tenant->created_at,
                'expires_at' => $pendingRegistration ? $pendingRegistration->expires_at : $tenant->created_at->addHours(168),
            ], 403);
        }

        tenancy()->initialize($tenant);

        // Redirect to the tenant dashboard
        return redirect('/dashboard');
    }

    /**
     * Access tenant from session fallback URL
     */
    public function accessTenantFromSession(Request $request)
    {
        $subdomain = session('pending_tenant_subdomain');

        if (!$subdomain) {
            return redirect('/');
        }

        return $this->accessTenant($request, $subdomain);
    }
}
