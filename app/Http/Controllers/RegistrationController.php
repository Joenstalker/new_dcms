<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationPending;
use App\Models\PendingRegistration;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SystemEarning;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stancl\Tenancy\Database\Models\Domain;
use Stripe\StripeClient;
use Stripe\Webhook;

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
            'data' => $validated,
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
            'available' => ! $exists,
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
            $base.'clinic',
            $base.'dental',
            $base.'care',
            substr($base, 0, min(10, strlen($base))), // Shortened
        ];

        foreach ($variants as $variant) {
            if (strlen($variant) >= 3 && ! in_array($variant, $usedSubdomains)) {
                $suggestions[] = $variant;
            }
            if (count($suggestions) >= 3) {
                break;
            }
        }

        // Add numeric variants if needed
        if (count($suggestions) < 3) {
            for ($i = 1; $i <= 5; $i++) {
                $variant = $base.$i;
                if (! in_array($variant, $usedSubdomains)) {
                    $suggestions[] = $variant;
                }
                if (count($suggestions) >= 3) {
                    break;
                }
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
            'subdomain' => ['required', 'string', 'min:3', 'max:63', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/'],
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'months' => 'nullable|integer|min:1|max:12',
        ]);

        $validated['subdomain'] = strtolower($validated['subdomain']);

        // Check if subdomain is already registered
        if (Domain::where('domain', $validated['subdomain'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This subdomain is already taken.',
            ], 400);
        }

        // Get the selected plan
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $months = $validated['billing_cycle'] === 'yearly' ? 12 : ($validated['months'] ?? 1);

        // Determine price based on billing cycle (unit price)
        $unitPrice = $validated['billing_cycle'] === 'yearly'
            ? $plan->price_yearly
            : $plan->price_monthly;

        // Total amount for initial record
        $totalPrice = $validated['billing_cycle'] === 'yearly'
            ? $plan->price_yearly
            : ($plan->price_monthly * $months);

        // Determine Stripe price ID
        $stripePriceId = $validated['billing_cycle'] === 'yearly'
            ? $plan->stripe_yearly_price_id
            : $plan->stripe_monthly_price_id;

        if (! $stripePriceId) {
            return response()->json([
                'success' => false,
                'message' => 'Selected plan is not available for payment. Please contact support.',
            ], 400);
        }

        try {
            // Get configurable settings from system settings
            $defaultTimeoutMinutes = SystemSetting::get('pending_timeout_default_minutes', 10080);
            $autoApproveEnabled = SystemSetting::get('pending_auto_approve_enabled', false);
            $reminderEnabled = SystemSetting::get('pending_reminder_global_enabled', true);

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
                    'amount_paid' => $totalPrice, // Save total expected amount
                    'status' => PendingRegistration::STATUS_PENDING,
                    'verification_token' => PendingRegistration::generateToken(),
                    'expires_at' => now('UTC')->addMinutes($defaultTimeoutMinutes),
                    'pending_timeout_minutes' => $defaultTimeoutMinutes,
                    'auto_approve_enabled' => $autoApproveEnabled,
                    'reminder_enabled' => $reminderEnabled,
                ]
            );

            $stripe = $this->getStripeClient();

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
                'months' => $months,
            ];

            // Build line items and subscription data
            $lineItems = [];
            $subscriptionData = [];

            if ($validated['billing_cycle'] === 'monthly' && $months > 1) {
                // Scenario: Prepaid multi-month (e.g. 3 months)
                // 1. One-time payment for the ENTIRE duration upfront
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'php',
                        'unit_amount' => (int) ($unitPrice * $months * 100), // Total upfront price in cents
                        'product_data' => [
                            'name' => "{$plan->name} Subscription ({$months} Months)",
                            'description' => "Initial payment for {$months} months of {$plan->name} plan.",
                        ],
                    ],
                    'quantity' => 1,
                ];

                // 2. The recurring subscription (starts AFTER the prepaid months)
                $lineItems[] = [
                    'price' => $stripePriceId,
                    'quantity' => 1,
                ];

                // Set trial end so the first recurring charge happens AFTER the prepaid duration
                $subscriptionData = [
                    'trial_end' => now()->addMonths($months)->timestamp,
                    'metadata' => $metadata,
                ];
            } else {
                // Scenario: Standard monthly (1 month) or Yearly (1 year)
                $lineItems[] = [
                    'price' => $stripePriceId,
                    'quantity' => 1,
                ];
                $subscriptionData = [
                    'metadata' => $metadata,
                ];
            }

            // Create Stripe Embedded Checkout session
            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'subscription',
                'ui_mode' => 'embedded',
                'subscription_data' => $subscriptionData,
                'return_url' => config('app.url').'/?payment=success&session_id={CHECKOUT_SESSION_ID}',
                'customer_email' => $validated['email'],
                'metadata' => $metadata,
            ]);

            // Update PendingRegistration with Stripe session ID
            $pendingRegistration->update([
                'stripe_session_id' => $session->id,
            ]);

            // Store in server session so /registration/pay can access without exposing secrets in URL
            session([
                'checkout_client_secret' => $session->client_secret,
                'checkout_session_id' => $session->id,
                'checkout_order_summary' => [
                    'clinic_name' => $validated['clinic_name'],
                    'plan_name' => $plan->name,
                    'billing_cycle' => $validated['billing_cycle'],
                    'months' => $months,
                    'amount' => $totalPrice,
                ],
            ]);

            return response()->json([
                'success' => true,
                'clientSecret' => $session->client_secret,
                'sessionId' => $session->id,
                'redirect_url' => route('registration.pay'),
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe checkout creation failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment session. Please try again.',
            ], 500);
        }
    }

    /**
     * Show the full-page Secure Payment page (Stripe embedded checkout).
     * Session data passed via Laravel session, set during createCheckoutSession.
     */
    public function showPaymentPage(Request $request)
    {
        $clientSecret = session('checkout_client_secret');
        $sessionId = session('checkout_session_id');
        $orderSummary = session('checkout_order_summary');

        if (! $clientSecret || ! $sessionId) {
            return redirect('/')->with('error', 'Invalid payment session. Please try again.');
        }

        return view('registration.secure-payment', [
            'clientSecret' => $clientSecret,
            'sessionId' => $sessionId,
            'order' => $orderSummary,
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Render the professional printable receipt page.
     */
    public function downloadReceipt(Request $request, string $session_id)
    {
        try {
            $stripe = $this->getStripeClient();
            $session = $stripe->checkout->sessions->retrieve($session_id, [
                'expand' => ['subscription.latest_invoice', 'line_items'],
            ]);

            $pendingId = $session->metadata->pending_registration_id ?? null;
            $pending = $pendingId ? PendingRegistration::find($pendingId) : null;

            if (! $pending) {
                abort(404, 'Registration not found.');
            }

            // Load plan features from the plan model
            $plan = SubscriptionPlan::find($pending->plan_id);
            $features = $plan ? (is_string($plan->features) ? json_decode($plan->features, true) : $plan->features) : [];

            // Build 12-digit digital signature: YEAR(4) + MMDD(4) + padded ms(4)
            $now = now();
            $ms = (int) ($now->getPreciseTimestamp(3) % 10000); // last 4 digits of ms
            $signature = $now->format('Y').$now->format('md').str_pad($ms, 4, '0', STR_PAD_LEFT);

            return view('registration.receipt', [
                'registration' => $pending,
                'plan' => $plan,
                'features' => $features,
                'sessionId' => $session_id,
                'signature' => $signature,
                'amountPaid' => $pending->amount_paid ?? ($session->amount_total / 100),
                'paidAt' => now()->format('F d, Y \a\t h:i A'),
            ]);

        } catch (\Exception $e) {
            Log::error('Receipt generation failed: '.$e->getMessage());
            abort(500, 'Could not generate receipt. Please contact support.');
        }
    }

    /**
     * Handle successful payment - create tenant with pending status
     * Tenant will appear in Tenants table with 'pending' status for admin review
     */
    public function handleSuccess(Request $request)
    {
        $sessionId = $request->session_id;

        if (! $sessionId) {
            return view('errors.registration-failed', [
                'code' => '400',
                'title' => 'Session Error',
                'message' => 'No payment session found.',
            ]);
        }

        try {
            $stripe = $this->getStripeClient();
            $session = $stripe->checkout->sessions->retrieve($sessionId, [
                'expand' => ['subscription.latest_invoice'],
            ]);

            if ($session->payment_status !== 'paid') {
                return view('errors.registration-failed', [
                    'code' => '402',
                    'title' => 'Payment Incomplete',
                    'message' => 'Payment was not completed. Please try again.',
                ]);
            }

            $result = $this->processSuccessfulRegistration($session);

            if (! $result['success']) {
                $code = $result['error'] === 'not_found' ? '404' : '500';

                return view('errors.registration-failed', [
                    'code' => $code,
                    'title' => 'Registration Error',
                    'message' => $result['message'],
                ]);
            }

            if ($result['already_processed'] ?? false) {
                $pendingRegistration = $result['registration'];
                if ($pendingRegistration->status === PendingRegistration::STATUS_APPROVED) {
                    return redirect()->to('/?already-approved=true');
                }

                return redirect()->to('/?registration-status='.$pendingRegistration->status);
            }

            return view('emails.registration.payment-received', [
                'registration' => $result['registration'],
                'sessionId' => $sessionId,
            ]);
        } catch (\Exception $e) {
            Log::error('Payment success handling failed: '.$e->getMessage());

            return view('errors.registration-failed', [
                'code' => '500',
                'title' => 'Verification Failed',
                'message' => 'Payment verification failed. Please contact support.',
            ]);
        }
    }

    /**
     * Webhook handler for Stripe events
     */
    public function handleWebhook(Request $request)
    {
        try {
            $event = $request->attributes->get('stripe_event');
            $stripe = $this->getStripeClient();

            // Backward-compatible fallback if middleware is bypassed in non-standard environments.
            if (! $event) {
                $event = Webhook::constructEvent(
                    $request->getContent(),
                    (string) $request->header('stripe-signature'),
                    config('services.stripe.webhook_secret')
                );
            }

            // Handle the event
            switch ($event->type) {
                case 'checkout.session.completed':
                    $sessionData = $event->data->object;
                    if ($sessionData->payment_status === 'paid') {
                        $session = $stripe->checkout->sessions->retrieve($sessionData->id, [
                            'expand' => ['subscription.latest_invoice'],
                        ]);
                        $this->processSuccessfulRegistration($session);
                    }
                    break;

                case 'customer.subscription.updated':
                    $subscriptionObj = $event->data->object;
                    $this->handleSubscriptionUpdated($subscriptionObj);
                    break;

                case 'customer.subscription.deleted':
                    $subscriptionObj = $event->data->object;
                    $this->handleSubscriptionDeleted($subscriptionObj);
                    break;

                default:
                    Log::info('Unhandled Stripe event: '.$event->type);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed: '.$e->getMessage());

            return response()->json(['error' => 'Webhook processing failed'], 400);
        }
    }

    /**
     * Complete registration after Stripe Embedded Checkout onComplete fires.
     * Called via AJAX from PaymentModal.vue — returns JSON instead of a Blade view.
     */
    public function completeRegistration(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $stripe = $this->getStripeClient();
            $session = $stripe->checkout->sessions->retrieve($validated['session_id'], [
                'expand' => ['subscription.latest_invoice'],
            ]);

            Log::info('completeRegistration: session retrieved', [
                'session_id' => $session->id,
                'status' => $session->status,
                'payment_status' => $session->payment_status,
            ]);

            if ($session->status !== 'complete') {
                return response()->json(['success' => false, 'message' => 'Payment not yet completed. Status: '.$session->status], 402);
            }

            $result = $this->processSuccessfulRegistration($session);

            if (! $result['success']) {
                $status = $result['error'] === 'not_found' ? 404 : 500;

                return response()->json(['success' => false, 'message' => $result['message']], $status);
            }

            $pendingRegistration = $result['registration'];

            if ($result['already_processed'] ?? false) {
                return response()->json([
                    'success' => true,
                    'already_processed' => true,
                    'registration' => [
                        'clinic_name' => $pendingRegistration->clinic_name,
                        'first_name' => $pendingRegistration->first_name,
                        'last_name' => $pendingRegistration->last_name,
                        'email' => $pendingRegistration->email,
                        'amount_paid' => $pendingRegistration->amount_paid,
                        'expires_at' => $pendingRegistration->expires_at->timestamp * 1000,
                        'subdomain' => $pendingRegistration->subdomain,
                    ],
                    'server_time' => now('UTC')->timestamp * 1000,
                ]);
            }

            return response()->json([
                'success' => true,
                'registration' => [
                    'clinic_name' => $pendingRegistration->clinic_name,
                    'first_name' => $pendingRegistration->first_name,
                    'last_name' => $pendingRegistration->last_name,
                    'email' => $pendingRegistration->email,
                    'amount_paid' => $pendingRegistration->amount_paid,
                    'expires_at' => $pendingRegistration->expires_at->timestamp * 1000,
                    'subdomain' => $pendingRegistration->subdomain,
                ],
                'server_time' => now('UTC')->timestamp * 1000,
            ]);

        } catch (\Exception $e) {
            Log::error('completeRegistration failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Payment verification failed.'], 500);
        }
    }

    /**
     * Shared logic to process successful Stripe Session
     * Returns an array with outcome details
     */
    protected function processSuccessfulRegistration($session)
    {
        $metadata = $session->metadata;
        $pendingRegistrationId = $metadata->pending_registration_id ?? null;

        $pendingRegistration = PendingRegistration::find($pendingRegistrationId);

        if (! $pendingRegistration) {
            Log::error('Pending registration not found: '.$pendingRegistrationId);

            return ['success' => false, 'error' => 'not_found', 'message' => 'Your registration record could not be found.'];
        }

        // Check if tenant already exists for this subdomain — more reliable than just status check
        $tenantId = strtolower(trim($pendingRegistration->subdomain));
        if ($pendingRegistration->stripe_payment_intent_id && Tenant::where('id', $tenantId)->exists()) {
            return ['success' => true, 'already_processed' => true, 'registration' => $pendingRegistration];
        }

        DB::beginTransaction();

        try {
            $tenantId = strtolower(trim($pendingRegistration->subdomain));

            // Use firstOrCreate to handle retry scenarios where a previous attempt
            // partially created the tenant (stancl/tenancy creates the DB outside
            // the Laravel transaction, so rollback doesn't undo it)
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                $tenant = Tenant::create([
                    'id' => $tenantId,
                    'name' => $pendingRegistration->clinic_name,
                    'owner_name' => $pendingRegistration->first_name.' '.$pendingRegistration->last_name,
                    'email' => $pendingRegistration->email,
                    'phone' => $pendingRegistration->phone,
                    'street' => $pendingRegistration->street,
                    'region' => $pendingRegistration->region,
                    'barangay' => $pendingRegistration->barangay,
                    'city' => $pendingRegistration->city,
                    'province' => $pendingRegistration->province,
                    'status' => 'pending',
                    'enabled_features' => Tenant::getDefaultFeatures(),
                ]);
            }

            if (! $tenant->domains()->where('domain', $pendingRegistration->subdomain)->exists()) {
                $domain = $tenant->domains()->create(['domain' => $pendingRegistration->subdomain]);
                $tenant->update(['domain_id' => $domain->id]);
            }

            // Extract the subscription ID string — Stripe may return a string or an expanded object
            $stripeSubscription = $session->subscription ?? null;
            $stripeSubscriptionId = is_string($stripeSubscription) ? $stripeSubscription : ($stripeSubscription->id ?? null);

            // Get the duration from metadata (defaults to 1 if not found)
            $monthsPaid = (int) ($metadata->months ?? 1);
            $billingCycleEnd = $pendingRegistration->billing_cycle === 'yearly'
                ? now()->addYear()
                : now()->addMonths($monthsPaid);

            $tenant->subscriptions()->updateOrCreate(
                ['stripe_id' => $stripeSubscriptionId],
                [
                    'tenant_id' => $tenantId, // Ensure tenant_id is set
                    'subscription_plan_id' => $pendingRegistration->subscription_plan_id,
                    'stripe_status' => 'active',
                    'stripe_price' => $session->amount_total / 100,
                    'billing_cycle' => $pendingRegistration->billing_cycle,
                    'payment_method' => 'card',
                    'payment_status' => 'paid',
                    'billing_cycle_end' => $billingCycleEnd,
                    'ends_at' => $billingCycleEnd,
                ]
            );

            $paymentIntentId = is_string($session->payment_intent) ? $session->payment_intent : ($session->payment_intent->id ?? null);
            if (! $paymentIntentId && $stripeSubscription && ! is_string($stripeSubscription)) {
                $invoice = $stripeSubscription->latest_invoice ?? null;
                $paymentIntentId = is_string($invoice->payment_intent ?? null) ? $invoice->payment_intent : ($invoice->payment_intent->id ?? null);
            }

            $timeoutMinutes = $pendingRegistration->getEffectiveTimeoutMinutes();

            $pendingRegistration->update([
                'stripe_session_id' => $session->id,
                'stripe_payment_intent_id' => $paymentIntentId,
                'amount_paid' => $session->amount_total / 100,
                'status' => PendingRegistration::STATUS_PENDING,
                'expires_at' => now('UTC')->addMinutes($timeoutMinutes),
            ]);

            SystemEarning::create([
                'tenant_id' => $tenantId,
                'amount' => $session->amount_total / 100,
                'currency' => strtoupper($session->currency ?? 'PHP'),
                'stripe_payment_intent_id' => $paymentIntentId,
                'stripe_session_id' => $session->id,
                'description' => 'Tenant Registration Payment',
                'paid_at' => now('UTC'),
            ]);

            DB::commit();

            // Notify admins (moved outside transaction)
            try {
                $notificationService = app(NotificationService::class);
                $notificationService->notifyAdmins(
                    'new_pending_tenant',
                    'New Tenant Pending Review',
                    "A new clinic '{$pendingRegistration->clinic_name}' has registered and payment received. Please review.",
                    [
                        'tenant_id' => $tenantId,
                        'clinic_name' => $pendingRegistration->clinic_name,
                        'subdomain' => $pendingRegistration->subdomain,
                        'admin_email' => $pendingRegistration->email,
                        'amount_paid' => $session->amount_total / 100,
                    ],
                    'both'
                );

                // Send pending registration email
                Mail::to($pendingRegistration->email)->send(new RegistrationPending($pendingRegistration));
            } catch (\Exception $e) {
                Log::warning('Post-registration notification failed: '.$e->getMessage());
                // Don't fail the whole request just because an email failed
            }

            return ['success' => true, 'already_processed' => false, 'registration' => $pendingRegistration];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tenant creation failed in processSuccessfulRegistration: '.$e->getMessage());

            return ['success' => false, 'error' => 'server_error', 'message' => 'Failed to finalize your setup: '.$e->getMessage()];
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
        $domain = Domain::where('domain', $subdomain)->first();

        if (! $domain) {
            abort(404, 'Clinic not found');
        }

        // Initialize tenancy for this tenant
        $tenant = $domain->tenant;

        if ($request->boolean('preview')) {
            $bootstrap = $request->session()->get('tenant_preview_bootstrap');

            $isAuthorizedPreview = is_array($bootstrap)
                && ($bootstrap['tenant_id'] ?? null) === (string) $tenant->getTenantKey()
                && ($bootstrap['subdomain'] ?? null) === (string) $subdomain
                && (int) ($bootstrap['admin_user_id'] ?? 0) === (int) optional($request->user())->id;

            if (! $isAuthorizedPreview) {
                abort(403, 'Invalid tenant preview session.');
            }

            tenancy()->initialize($tenant);

            $previewUser = User::role('Owner')->first()
                ?? User::role('Admin')->first()
                ?? User::query()->orderBy('id')->first();

            tenancy()->end();

            if (! $previewUser) {
                abort(422, 'Cannot start preview because this tenant has no users to impersonate.');
            }

            $request->session()->put('tenant_preview_active', [
                'active' => true,
                'tenant_id' => (string) $tenant->getTenantKey(),
                'subdomain' => (string) $subdomain,
                'admin_user_id' => (int) optional($request->user())->id,
                'tenant_user_id' => (int) $previewUser->id,
                'started_at' => now()->toIso8601String(),
            ]);
            $request->session()->forget('tenant_preview_bootstrap');
        }

        // Check if tenant is pending verification
        if ($tenant->status === 'pending') {
            // Find associated pending registration to get correct expiry time
            $pendingRegistration = PendingRegistration::where('subdomain', $subdomain)
                ->where('status', PendingRegistration::STATUS_PENDING)
                ->first();
            $timeoutMinutes = $pendingRegistration ? $pendingRegistration->getEffectiveTimeoutMinutes() : SystemSetting::get('pending_timeout_default_minutes', 10080);

            return response()->view('errors.pending', [
                'tenant' => $tenant,
                'created_at' => $tenant->created_at,
                'expires_at' => $pendingRegistration ? $pendingRegistration->expires_at : $tenant->created_at->addMinutes(10080),
                'timeout_minutes' => $timeoutMinutes,
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

        if (! $subdomain) {
            return redirect('/');
        }

        return $this->accessTenant($request, $subdomain);
    }

    /**
     * Exit admin tenant preview mode.
     */
    public function exitPreview(Request $request)
    {
        $request->session()->forget('tenant_preview_bootstrap');
        $request->session()->forget('tenant_preview_active');

        if ($request->user() && method_exists($request->user(), 'hasRole') && $request->user()->hasRole('Admin')) {
            return redirect()->route('admin.tenants.index')->with('success', 'Tenant preview mode ended.');
        }

        return redirect('/');
    }

    /**
     * Handle Stripe customer.subscription.updated webhook
     */
    protected function handleSubscriptionUpdated($subscriptionObj)
    {
        try {
            $stripeSubscriptionId = $subscriptionObj->id;

            // Find subscription by stripe_id in DB
            $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();

            if (! $subscription) {
                Log::warning("Webhook updated: Subscription not found for Stripe ID: {$stripeSubscriptionId}");

                return;
            }

            // Get new price ID
            $priceId = $subscriptionObj->items->data[0]->price->id ?? null;

            if (! $priceId) {
                Log::warning("Webhook updated: No price ID found for Stripe ID: {$stripeSubscriptionId}");

                return;
            }

            // Find matching SubscriptionPlan
            $plan = SubscriptionPlan::where('stripe_monthly_price_id', $priceId)
                ->orWhere('stripe_yearly_price_id', $priceId)
                ->first();

            if (! $plan) {
                Log::warning("Webhook updated: Unknown Stripe price ID: {$priceId}");

                return;
            }

            // Determine if the billing cycle changed based on which price matched
            $billingCycle = ($plan->stripe_yearly_price_id === $priceId) ? 'yearly' : 'monthly';

            // Get current plan info for notification
            $oldPlanName = $subscription->plan->name ?? 'Unknown';
            $isUpgradeOrDowngrade = $subscription->subscription_plan_id !== $plan->id;
            $status = $subscriptionObj->status;

            // Extract ends_at timestamp if provided
            $currentPeriodEnd = $subscriptionObj->current_period_end ?? null;
            $endsAt = $currentPeriodEnd ? Carbon::createFromTimestamp($currentPeriodEnd) : null;

            // Update local DB
            $subscription->update([
                'subscription_plan_id' => $plan->id,
                'stripe_price' => $subscriptionObj->items->data[0]->price->unit_amount / 100,
                'stripe_status' => $status,
                'billing_cycle' => $billingCycle,
                'payment_status' => $status === 'active' ? 'paid' : 'unpaid',
                'billing_cycle_end' => $endsAt,
                'payment_method' => 'card', // Revert to Stripe managed
            ]);

            // Notify Admin
            $notificationService = app(NotificationService::class);

            if ($isUpgradeOrDowngrade) {
                $tenant = $subscription->tenant;
                if ($tenant) {
                    $notificationService->notifyAdmins(
                        'subscription_updated',
                        'Tenant Plan Updated',
                        "Tenant '{$tenant->name}' has changed their plan from {$oldPlanName} to {$plan->name} ({$billingCycle}).",
                        [
                            'tenant_id' => $tenant->id,
                            'clinic_name' => $tenant->name,
                            'new_plan' => $plan->name,
                            'billing_cycle' => $billingCycle,
                            'status' => $status,
                        ],
                        'both'
                    );
                }
            }

            Log::info("Webhook processed: Subscription {$stripeSubscriptionId} updated to plan {$plan->name}");

        } catch (\Exception $e) {
            Log::error('Failed to handle subscription updated: '.$e->getMessage());
        }
    }

    /**
     * Handle Stripe customer.subscription.deleted webhook
     */
    protected function handleSubscriptionDeleted($subscriptionObj)
    {
        try {
            $stripeSubscriptionId = $subscriptionObj->id;

            $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();

            if (! $subscription) {
                Log::warning("Webhook deleted: Subscription not found for Stripe ID: {$stripeSubscriptionId}");

                return;
            }

            // Guard: If plan was manually overridden by admin, ignore Stripe deletion
            if ($subscription->payment_method === 'admin_override') {
                Log::info("Webhook ignored: Subscription {$stripeSubscriptionId} was deleted in Stripe but is currently under Admin Override.");

                return;
            }

            $subscription->update([
                'stripe_status' => 'canceled',
            ]);

            $tenant = $subscription->tenant;

            // Notify Admin
            if ($tenant) {
                $notificationService = app(NotificationService::class);
                $notificationService->notifyAdmins(
                    'subscription_canceled',
                    'Tenant Subscription Canceled',
                    "Tenant '{$tenant->name}' has canceled their subscription via Stripe.",
                    [
                        'tenant_id' => $tenant->id,
                        'clinic_name' => $tenant->name,
                    ],
                    'both'
                );
            }

            Log::info("Webhook processed: Subscription {$stripeSubscriptionId} canceled");

        } catch (\Exception $e) {
            Log::error('Failed to handle subscription deleted: '.$e->getMessage());
        }
    }

    /**
     * Get the StripeClient instance.
     * Overridden in tests to mock Stripe.
     */
    protected function getStripeClient(): StripeClient
    {
        return app(StripeClient::class);
    }
}
