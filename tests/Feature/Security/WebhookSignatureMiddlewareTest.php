<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyGitHubWebhookSignature;
use App\Http\Middleware\VerifyStripeWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookSignatureMiddlewareTest extends TestCase
{
    #[Test]
    public function github_webhook_middleware_accepts_valid_signature(): void
    {
        config(['services.github.webhook_secret' => 'github_test_secret']);

        $payload = json_encode(['action' => 'released', 'release' => ['tag_name' => 'v1.0.0']], JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $payload, 'github_test_secret');

        $request = Request::create('/github/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Hub-Signature-256', $signature);
        $request->headers->set('X-GitHub-Delivery', 'delivery-1');

        $middleware = new VerifyGitHubWebhookSignature;
        $response = $middleware->handle($request, fn () => response()->json(['ok' => true], 200));

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function github_webhook_middleware_blocks_replay_delivery(): void
    {
        config(['services.github.webhook_secret' => 'github_test_secret']);

        $payload = json_encode(['action' => 'released'], JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $payload, 'github_test_secret');
        $delivery = 'delivery-replay';

        $request = Request::create('/github/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Hub-Signature-256', $signature);
        $request->headers->set('X-GitHub-Delivery', $delivery);

        Cache::add('webhook:github:delivery:'.$delivery, true, now()->addMinutes(10));

        $middleware = new VerifyGitHubWebhookSignature;
        $response = $middleware->handle($request, fn () => response()->json(['ok' => true], 200));

        $this->assertSame(409, $response->getStatusCode());
    }

    #[Test]
    public function stripe_webhook_middleware_accepts_valid_signature_and_sets_event(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

        $payload = json_encode([
            'id' => 'evt_test_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signedPayload = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $signedPayload, 'whsec_test_secret');
        $header = "t={$timestamp},v1={$signature}";

        $request = Request::create('/registration/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('stripe-signature', $header);

        $middleware = new VerifyStripeWebhookSignature;
        $response = $middleware->handle($request, function (Request $request) {
            $event = $request->attributes->get('stripe_event');

            return response()->json(['event_id' => $event->id], 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('evt_test_1', data_get(json_decode((string) $response->getContent(), true), 'event_id'));
    }
}
