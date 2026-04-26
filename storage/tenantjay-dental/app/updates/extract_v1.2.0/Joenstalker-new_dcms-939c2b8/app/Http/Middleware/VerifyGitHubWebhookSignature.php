<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyGitHubWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.github.webhook_secret', '');
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $deliveryId = (string) $request->header('X-GitHub-Delivery', '');

        if ($secret === '' || $signature === '' || $deliveryId === '') {
            return response()->json(['message' => 'Unauthorized webhook request.'], 401);
        }

        $payload = $request->getContent();
        $expected = 'sha256='.hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 403);
        }

        $replayKey = 'webhook:github:delivery:'.$deliveryId;
        $isFirstSeen = Cache::add($replayKey, true, now()->addMinutes(10));

        if (! $isFirstSeen) {
            return response()->json(['message' => 'Duplicate webhook delivery.'], 409);
        }

        return $next($request);
    }
}
