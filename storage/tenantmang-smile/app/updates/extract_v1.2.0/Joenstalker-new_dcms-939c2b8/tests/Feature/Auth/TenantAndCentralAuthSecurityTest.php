<?php

namespace Tests\Feature\Auth;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Central vs tenant authentication boundaries (merged from redundant Security\AuthenticationTest).
 *
 * DomainIsolationTest covers login host routing; this file covers dashboard guest access and central login rules.
 */
class TenantAndCentralAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteTenantSqliteFiles();

        Event::fake([
            \Stancl\Tenancy\Events\TenantCreated::class,
            \Stancl\Tenancy\Events\TenantSaved::class,
            \Stancl\Tenancy\Events\TenantUpdated::class,
            \Stancl\Tenancy\Events\DatabaseCreated::class,
        ]);
    }

    protected function tearDown(): void
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            tenancy()->end();
        }

        $this->deleteTenantSqliteFiles();

        parent::tearDown();
    }

    /**
     * Protects against: unauthenticated access to tenant staff dashboards.
     */
    public function test_guest_cannot_access_tenant_dashboard(): void
    {
        $host = 'sec-auth-tenant.dcms.test';
        config(['app.url' => 'http://'.$host]);
        $this->withServerVariables([
            'HTTP_HOST' => $host,
            'SERVER_NAME' => $host,
        ]);

        $tenant = Tenant::create(['id' => 'sec-auth-tenant', 'name' => 'Auth Tenant']);
        $tenant->domains()->create(['domain' => explode('.', $host)[0]]);

        $plan = SubscriptionPlan::create([
            'name' => 'Sec Auth Plan',
            'slug' => 'sec-auth-plan',
            'price_monthly' => 1,
            'stripe_monthly_price_id' => 'price_sec_auth',
            'max_users' => 10,
            'max_patients' => 100,
        ]);
        $plan->features()->detach();

        Subscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'stripe_status' => 'active',
            'payment_status' => 'paid',
            'billing_cycle' => 'monthly',
            'stripe_id' => 'sub_sec_auth',
        ]);

        $this->provisionTenantSqliteAndMigrate($tenant);

        $this->get('http://'.$host.'/dashboard')
            ->assertRedirect();
    }

    /**
     * Protects against: non-admin users using the central admin login (domain / role boundary).
     */
    public function test_non_admin_cannot_authenticate_via_central_login(): void
    {
        User::factory()->create([
            'email' => 'staff-only@example.test',
            'password' => 'password',
            'is_admin' => false,
        ]);

        $this->post(route('login'), [
            'email' => 'staff-only@example.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    /**
     * Protects against: credential stuffing — wrong password must not authenticate.
     */
    public function test_central_login_with_wrong_password_does_not_authenticate(): void
    {
        User::factory()->create([
            'email' => 'admin-brute@example.test',
            'password' => 'correct-password',
            'is_admin' => true,
        ]);

        $this->post(route('login'), [
            'email' => 'admin-brute@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
