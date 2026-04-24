<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\Tenant\Support\SupportController;
use App\Http\Middleware\SecurityHeaders;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityEdgeCaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        tenancy()->initialize(Tenant::make(['id' => 'tenant-a']));
    }

    #[Test]
    public function tenant_user_cannot_access_another_tenants_support_ticket(): void
    {
        $user = $this->authorizedUserMock();
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('userResolver')->andReturn(fn () => $user);

        $controller = new SupportController;
        $ticket = new SupportTicket(['tenant_id' => 'tenant-b']);

        try {
            $controller->show($ticket);
            $this->fail('Expected HttpResponseException for cross-tenant access.');
        } catch (AuthorizationException $exception) {
            $this->assertSame('This action is unauthorized.', $exception->getMessage());
        } catch (HttpResponseException $exception) {
            $response = $exception->getResponse();
            $this->assertSame(403, $response->getStatusCode());

            $payload = json_decode((string) $response->getContent(), true);
            $this->assertFalse($payload['success']);
            $this->assertSame(403, $payload['code']);
        }
    }

    #[Test]
    public function user_without_required_role_or_permission_is_denied_support_access(): void
    {
        $user = $this->unauthorizedUserMock();
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('userResolver')->andReturn(fn () => $user);

        $controller = new SupportController;

        try {
            $controller->index();
            $this->fail('Expected HttpResponseException for unauthorized support access.');
        } catch (HttpResponseException $exception) {
            $response = $exception->getResponse();
            $this->assertSame(403, $response->getStatusCode());

            $payload = json_decode((string) $response->getContent(), true);
            $this->assertFalse($payload['success']);
            $this->assertSame(403, $payload['code']);
        }
    }

    #[Test]
    public function security_headers_middleware_adds_expected_headers(): void
    {
        $middleware = new SecurityHeaders;
        $request = Request::create('/', 'GET');

        $response = $middleware->handle($request, fn () => response('ok', 200));

        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
    }

    private function authorizedUserMock()
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->with('Owner')->andReturn(true);
        $user->shouldReceive('can')->with('access support chat')->andReturn(false);
        $user->shouldReceive('checkPermissionTo')->andReturn(false);

        return $user;
    }

    private function unauthorizedUserMock()
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->with('Owner')->andReturn(false);
        $user->shouldReceive('can')->with('access support chat')->andReturn(false);
        $user->shouldReceive('checkPermissionTo')->andReturn(false);

        return $user;
    }
}
