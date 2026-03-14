<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class BillingPortalController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $tenant = tenant();

        if (!$tenant) {
            return redirect()->route('tenant.dashboard')
                ->with('error', 'Billing portal is only available within a clinic context.');
        }

        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->whereIn('stripe_status', ['active', 'past_due', 'trialing'])
            ->latest()
            ->first();

        if (!$subscription || !$subscription->stripe_id) {
            return redirect()->route('settings.index')
                ->with('error', 'No active subscription found.');
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $stripeSubscription = $stripe->subscriptions->retrieve($subscription->stripe_id);
            $customerId = $stripeSubscription->customer;

            if (!$customerId) {
                return redirect()->route('settings.index')
                    ->with('error', 'Could not find your billing account.');
            }

            $appUrl = config('app.url');
            $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?? 'http';
            $host = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';
            $port = parse_url($appUrl, PHP_URL_PORT) ? ':' . parse_url($appUrl, PHP_URL_PORT) : '';
            $subdomain = $tenant->domains()->first()?->domain ?? '';
            $returnUrl = "{$scheme}://{$subdomain}.{$host}{$port}/settings";

            $portalSession = $stripe->billingPortal->sessions->create([
                'customer'   => $customerId,
                'return_url' => $returnUrl,
            ]);

            Log::info('Stripe Customer Portal session created.', [
                'tenant_id'   => $tenant->getTenantKey(),
                'customer_id' => $customerId,
            ]);

            return redirect()->away($portalSession->url);

        } catch (\Exception $e) {
            Log::error('Stripe Customer Portal failed: ' . $e->getMessage(), [
                'tenant_id' => $tenant->getTenantKey(),
            ]);

            return redirect()->route('settings.index')
                ->with('error', 'Failed to open billing portal.');
        }
    }
}
