<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureTenantSessionIsolation;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verifies role/permission middleware denies access where appropriate (Spatie).
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private string $host;

    protected function setUp(): void
    {
        parent::setUp();

        // HandleInertiaRequests runs before tenant bootstrap in the web stack; it logs out staff when tenant() is still null.
        // CheckSubscription is exercised elsewhere; here we isolate Spatie permission denial.
        $this->withoutMiddleware([
            HandleInertiaRequests::class,
            CheckSubscription::class,
            EnsureTenantSessionIsolation::class,
        ]);

        $this->deleteTenantSqliteFiles();

        Event::fake([
            \Stancl\Tenancy\Events\TenantCreated::class,
            \Stancl\Tenancy\Events\TenantSaved::class,
            \Stancl\Tenancy\Events\TenantUpdated::class,
            \Stancl\Tenancy\Events\DatabaseCreated::class,
        ]);

        $this->host = 'sec-authz.dcms.test';
        config(['app.url' => 'http://'.$this->host]);
        $this->withServerVariables([
            'HTTP_HOST' => $this->host,
            'SERVER_NAME' => $this->host,
        ]);

        $this->tenant = Tenant::create(['id' => 'sec-authz', 'name' => 'AuthZ Tenant']);
        // InitializeTenancyBySubdomain resolves `domains.domain` against the first hostname label only (see RegistrationController).
        $this->tenant->domains()->create(['domain' => explode('.', $this->host)[0]]);

        $plan = SubscriptionPlan::create([
            'name' => 'Sec AuthZ Plan',
            'slug' => 'sec-authz-plan',
            'price_monthly' => 1,
            'stripe_monthly_price_id' => 'price_sec_authz',
            'max_users' => 50,
            'max_patients' => 500,
        ]);
        $plan->features()->detach();

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $plan->id,
            'stripe_status' => 'active',
            'payment_status' => 'paid',
            'billing_cycle' => 'monthly',
            'stripe_id' => 'sub_sec_authz',
        ]);

        $this->provisionTenantSqliteAndMigrate($this->tenant);

        tenancy()->initialize($this->tenant);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        tenancy()->end();
    }

    private function createTenantStaffUser(): User
    {
        tenancy()->initialize($this->tenant);

        $user = User::factory()->create(['email' => 'no-patients@example.test']);
        Role::firstOrCreate(['name' => 'Dentist']);
        $user->assignRole('Dentist');
        Permission::firstOrCreate(['name' => 'view patients']);
        $user->syncPermissions([]);

        tenancy()->end();

        return $user;
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
     * Protects against: horizontal privilege escalation (staff accessing modules without grants).
     */
    public function test_user_without_view_patients_permission_receives_403_on_patients_index(): void
    {
        $user = $this->createTenantStaffUser();

        $this->actingAsTenantUser($user, $this->tenant->id)
            ->get('http://'.$this->host.'/patients')
            ->assertForbidden();
    }

    /**
     * Protects against: non-admin users reaching central admin UI.
     */
    public function test_authenticated_non_admin_receives_403_on_admin_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    protected function actingAsTenantUser(User $user, string $tenantId): static
    {
        return $this->withSession([
            'tenant_authenticated' => true,
            'tenant_authenticated_tenant_id' => (string) $tenantId,
            'tenant_authenticated_user_id' => (int) $user->id,
        ])->actingAs($user);
    }
}
