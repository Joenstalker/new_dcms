<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class VerifyStripeWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.stripe.webhook_secret', '');
        $signature = (string) $request->header('stripe-signature', '');

        if ($secret === '' || $signature === '') {
            return response()->json(['message' => 'Unauthorized webhook request.'], 401);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $signature,
                $secret,
                300
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid webhook signature.'], 403);
        }

        $eventId = (string) ($event->id ?? '');
        if ($eventId === '') {
            return response()->json(['message' => 'Invalid webhook event.'], 400);
        }

        $replayKey = 'webhook:stripe:event:'.$eventId;
        $isFirstSeen = Cache::add($replayKey, true, now()->addMinutes(10));

        if (! $isFirstSeen) {
            return response()->json(['message' => 'Duplicate webhook event.'], 409);
        }

        $request->attributes->set('stripe_event', $event);

        return $next($request);
    }
}
