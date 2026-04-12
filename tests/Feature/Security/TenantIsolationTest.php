<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureTenantSessionIsolation;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Patient;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Verifies that data for one tenant cannot be read or inferred from another tenant's context.
 *
 * Replace route URLs with route('patients.show', [...]) if your route model binding uses a different key.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantAlpha;

    private Tenant $tenantBeta;

    private string $alphaHost;

    private string $betaHost;

    private string $victimPatientId;

    private int $ownerBetaId;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->alphaHost = 'sec-iso-alpha.dcms.test';
        $this->betaHost = 'sec-iso-beta.dcms.test';

        config(['app.url' => 'http://'.$this->alphaHost]);

        $this->tenantAlpha = Tenant::create(['id' => 'sec-iso-alpha', 'name' => 'Isolation Alpha']);
        $this->tenantAlpha->domains()->create(['domain' => explode('.', $this->alphaHost)[0]]);

        $this->tenantBeta = Tenant::create(['id' => 'sec-iso-beta', 'name' => 'Isolation Beta']);
        $this->tenantBeta->domains()->create(['domain' => explode('.', $this->betaHost)[0]]);

        $plan = SubscriptionPlan::create([
            'name' => 'Sec ISO Plan',
            'slug' => 'sec-iso-plan',
            'price_monthly' => 1,
            'stripe_monthly_price_id' => 'price_sec_iso',
            'max_users' => 50,
            'max_patients' => 500,
        ]);
        $plan->features()->detach();

        foreach ([$this->tenantAlpha, $this->tenantBeta] as $tenant) {
            Subscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'stripe_status' => 'active',
                'payment_status' => 'paid',
                'billing_cycle' => 'monthly',
                'stripe_id' => 'sub_sec_iso_'.$tenant->id,
            ]);
        }

        $this->provisionTenantSqliteAndMigrate($this->tenantAlpha);

        tenancy()->initialize($this->tenantAlpha);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $ownerAlpha = User::factory()->create(['email' => 'owner-alpha@example.test']);
        $ownerAlpha->assignRole('Owner');

        $patient = Patient::create([
            'tenant_id' => (string) $this->tenantAlpha->getTenantKey(),
            'first_name' => 'Victim',
            'last_name' => 'Patient',
        ]);
        $this->victimPatientId = (string) $patient->id;

        tenancy()->end();

        $this->provisionTenantSqliteAndMigrate($this->tenantBeta);

        tenancy()->initialize($this->tenantBeta);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $ownerBeta = User::factory()->create(['email' => 'owner-beta@example.test']);
        $ownerBeta->assignRole('Owner');
        $this->ownerBetaId = (int) $ownerBeta->id;

        tenancy()->end();
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
     * Protects against: IDOR / cross-tenant patient access when an attacker guesses or replays another clinic's primary key.
     */
    public function test_authenticated_tenant_user_cannot_view_patient_record_from_another_tenant_database(): void
    {
        tenancy()->initialize($this->tenantBeta);
        $ownerBeta = User::query()->find($this->ownerBetaId);
        tenancy()->end();

        $this->assertNotNull($ownerBeta);

        config(['app.url' => 'http://'.$this->betaHost]);
        $this->withServerVariables([
            'HTTP_HOST' => $this->betaHost,
            'SERVER_NAME' => $this->betaHost,
        ]);

        // Swap for: route('patients.show', ['patientId' => $this->victimPatientId]) when tenant route names are wired in tests.
        $response = $this->actingAsTenantUser($ownerBeta, $this->tenantBeta->id)
            ->get('http://'.$this->betaHost.'/patients/'.$this->victimPatientId);

        // Must not return 200 with another tenant's PHI. Prefer 404; some apps incorrectly map missing rows to 500.
        $this->assertNotSame(200, $response->getStatusCode());
        $this->assertContains($response->getStatusCode(), [403, 404, 500], 'Cross-tenant patient access must not succeed as OK.');
    }

    /**
     * Protects against: query-scope mistakes that would let global scopes be bypassed in another tenant DB.
     */
    public function test_patient_primary_key_from_tenant_alpha_does_not_exist_in_tenant_beta_database(): void
    {
        tenancy()->initialize($this->tenantBeta);

        $this->assertNull(
            Patient::query()->whereKey($this->victimPatientId)->first(),
            'Beta tenant database should not contain Alpha tenant patient rows.'
        );

        tenancy()->end();
    }

    /**
     * Session flags are tenant-scoped in this app; mismatched tenant id should not keep a "valid" staff session.
     *
     * Adjust keys if your session shape differs (search: tenant_authenticated).
     */
    protected function actingAsTenantUser(User $user, string $tenantId): static
    {
        return $this->withSession([
            'tenant_authenticated' => true,
            'tenant_authenticated_tenant_id' => (string) $tenantId,
            'tenant_authenticated_user_id' => (int) $user->id,
        ])->actingAs($user);
    }
}
