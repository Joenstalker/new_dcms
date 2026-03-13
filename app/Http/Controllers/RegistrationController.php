<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            'admin_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
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
     */
    public function createCheckoutSession(Request $request)
    {
        $validated = $request->validate([
            'clinic_name' => 'required|string|max:255',
            'admin_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'subdomain' => 'required|string|max:63',
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

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
            $stripe = new StripeClient(config('services.stripe.secret'));

            // Create a metadata object to store registration data
            $metadata = [
                'clinic_name' => $validated['clinic_name'],
                'admin_name' => $validated['admin_name'],
                'email' => $validated['email'],
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
     * Handle successful payment - create tenant and user
     */
    public function handleSuccess(Request $request)
    {
        $sessionId = $request->session_id;

        if (!$sessionId) {
            return redirect('/?error=no_session');
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect('/?error=payment_not_completed');
            }

            $metadata = $session->metadata;

            // Check if already processed
            if (isset($metadata->processed) && $metadata->processed) {
                // Already processed, redirect to tenant domain
                $subdomain = $metadata->subdomain;
                return redirect()->away("http://{$subdomain}." . config('app.central_domain', 'dcms.test:8080'));
            }

            // Create tenant and user after successful payment
            DB::beginTransaction();

            try {
                // Generate unique tenant ID
                $tenantId = Str::uuid()->toString();

                // Create tenant
                $tenant = Tenant::create([
                    'id' => $tenantId,
                    'name' => $metadata->clinic_name,
                ]);

                // Create domain
                $tenant->domains()->create([
                    'domain' => $metadata->subdomain,
                ]);

                // Switch to tenant database
                tenancy()->initialize($tenant);

                // Create admin user
                $user = User::create([
                    'name' => $metadata->admin_name,
                    'email' => $metadata->email,
                    'password' => Hash::make($metadata->password),
                    'is_admin' => true,
                ]);

                // Create subscription record
                $subscription = $tenant->subscriptions()->create([
                    'subscription_plan_id' => $metadata->plan_id,
                    'stripe_id' => $session->subscription,
                    'stripe_status' => 'active',
                    'stripe_price' => $session->amount_total / 100,
                    'billing_cycle' => $metadata->billing_cycle,
                    'payment_method' => 'card',
                    'payment_status' => 'paid',
                ]);

                DB::commit();

                // Mark as processed in Stripe metadata
                $stripe->checkout->sessions->update($sessionId, [
                    'metadata' => array_merge((array)$metadata, ['processed' => true]),
                ]);

                // Redirect to tenant via fallback route (works without wildcard DNS)
                $subdomain = $metadata->subdomain;

                // Store the tenant info in session for the fallback route
                session(['pending_tenant_subdomain' => $subdomain]);

                // Redirect to the tenant route which works without wildcard DNS
                // This route will initialize the tenant and redirect to dashboard
                return redirect("/tenant/{$subdomain}");

            }
            catch (\Exception $e) {
                DB::rollBack();
                Log::error('Tenant creation failed: ' . $e->getMessage());
                return redirect('/?error=tenant_creation_failed');
            }

        }
        catch (\Exception $e) {
            Log::error('Payment success handling failed: ' . $e->getMessage());
            return redirect('/?error=verification_failed');
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
                    'max_users' => $plan->max_users,
                    'max_patients' => $plan->max_patients,
                    'features' => [
                        'qr_booking' => $plan->has_qr_booking,
                        'sms' => $plan->has_sms,
                        'branding' => $plan->has_branding,
                        'analytics' => $plan->has_analytics,
                        'priority_support' => $plan->has_priority_support,
                        'multi_branch' => $plan->has_multi_branch,
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
