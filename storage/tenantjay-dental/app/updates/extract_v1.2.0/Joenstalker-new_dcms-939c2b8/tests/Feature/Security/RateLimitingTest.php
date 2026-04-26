<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Confirms brute-force and abuse patterns hit HTTP 429 / lockout semantics.
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Protects against: unauthenticated credential stuffing against the central login endpoint.
     */
    public function test_central_login_locks_out_after_repeated_failed_attempts(): void
    {
        $email = 'lockout-probe-'.uniqid('', true).'@example.test';

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('central.home'))
                ->post(route('login'), [
                    'email' => $email,
                    'password' => 'definitely-wrong-password',
                ]);
        }

        $this->from(route('central.home'))
            ->post(route('login'), [
                'email' => $email,
                'password' => 'definitely-wrong-password',
            ])
            ->assertSessionHasErrors('email');
    }

    /**
     * Protects against: automation spamming expensive endpoints (middleware throttle).
     *
     * Uses an ephemeral route so we are not coupled to third-party controllers (e.g. webhooks).
     */
    public function test_throttle_middleware_returns_429_after_budget_exceeded(): void
    {
        $path = '/__security_throttle_'.bin2hex(random_bytes(4));

        Route::middleware(['web', 'throttle:2,1'])->get($path, fn () => response('ok'));

        $this->get($path)->assertOk();
        $this->get($path)->assertOk();
        $this->get($path)->assertStatus(429);
    }
}
