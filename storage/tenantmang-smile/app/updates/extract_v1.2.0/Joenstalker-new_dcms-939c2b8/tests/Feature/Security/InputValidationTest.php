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
 * Ensures user input is validated and malicious patterns do not break query execution.
 */
class InputValidationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private string $host;

    private User $owner;

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

        $this->host = 'sec-validate.dcms.test';
        config(['app.url' => 'http://'.$this->host]);
        $this->withServerVariables([
            'HTTP_HOST' => $this->host,
            'SERVER_NAME' => $this->host,
        ]);

        $this->tenant = Tenant::create(['id' => 'sec-validate', 'name' => 'Validate Tenant']);
        $this->tenant->domains()->create(['domain' => explode('.', $this->host)[0]]);

        $plan = SubscriptionPlan::create([
            'name' => 'Sec Validate Plan',
            'slug' => 'sec-validate-plan',
            'price_monthly' => 1,
            'stripe_monthly_price_id' => 'price_sec_val',
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
            'stripe_id' => 'sub_sec_val',
        ]);

        $this->provisionTenantSqliteAndMigrate($this->tenant);

        tenancy()->initialize($this->tenant);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->owner = User::factory()->create(['email' => 'owner-val@example.test']);
        $this->owner->assignRole('Owner');

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
     * Protects against: bad data writes and simple injection payloads in structured fields (server-side validation).
     */
    public function test_patient_store_rejects_invalid_email_with_unprocessable_response(): void
    {
        $response = $this->actingAsTenantUser($this->owner, $this->tenant->id)
            ->from('http://'.$this->host.'/patients/create')
            ->post('http://'.$this->host.'/patients', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'not-an-email',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertGreaterThanOrEqual(300, $response->getStatusCode());
        $this->assertLessThan(500, $response->getStatusCode());
    }

    /**
     * Protects against: classic SQL injection strings in search boxes — should not cause 5xx (bindings/escaping).
     */
    public function test_patient_index_search_accepts_sql_injection_like_string_without_server_error(): void
    {
        tenancy()->initialize($this->tenant);
        Patient::create([
            'tenant_id' => (string) $this->tenant->getTenantKey(),
            'first_name' => 'Safe',
            'last_name' => 'Record',
        ]);
        tenancy()->end();

        $payload = "x' OR '1'='1";

        $this->actingAsTenantUser($this->owner, $this->tenant->id)
            ->get('http://'.$this->host.'/patients?'.http_build_query(['search' => $payload]))
            ->assertOk();
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
