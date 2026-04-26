<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies state-changing web routes reject forged CSRF tokens before controllers run.
 *
 * Uses withSession() so the session-backed CSRF secret differs from the forged _token field.
 * On tenant hosts, VerifyCsrfToken runs inside the `web` stack before tenancy initialization,
 * so a 419 does not require a provisioned tenant database.
 */
class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Laravel's VerifyCsrfToken skips verification whenever runningInConsole() && runningUnitTests()
        // (e.g. `php artisan test`), which would make these assertions meaningless. Re-enable checks here only.
        $this->app->bind(ValidateCsrfToken::class, function ($app) {
            return new class ($app, $app['encrypter']) extends ValidateCsrfToken
            {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            };
        });
    }

    /**
     * Protects against: forged POST to central registration endpoints (session riding).
     *
     * Swap the path for route('registration.check-subdomain') if you name routes in tests.
     */
    public function test_central_registration_post_with_invalid_csrf_token_returns_page_expired(): void
    {
        $centralHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $response = $this->withSession(['_token' => 'expected-token'])
            ->call('POST', '/registration/check-subdomain', [
                '_token' => 'wrong-token',
                'subdomain' => 'validname',
            ], [], [], [
                'HTTP_HOST' => $centralHost,
                'SERVER_NAME' => $centralHost,
            ]);

        $response->assertStatus(419);
    }

    /**
     * Protects against: forged POST to the public tenant landing concern form.
     *
     * Swap for route('tenant.concern.store', [], false) when generating relative URLs from a tenant base.
     */
    public function test_tenant_concern_post_with_invalid_csrf_token_returns_page_expired(): void
    {
        $host = 'sec-csrf.dcms.test';

        $response = $this->withSession(['_token' => 'expected-token'])
            ->call('POST', '/concerns', [
                '_token' => 'wrong-token',
                'name' => 'Tester',
                'email' => 'tester@example.com',
                'message' => 'Concern body text for validation if CSRF passed.',
            ], [], [], [
                'HTTP_HOST' => $host,
                'SERVER_NAME' => $host,
            ]);

        $response->assertStatus(419);
    }
}
