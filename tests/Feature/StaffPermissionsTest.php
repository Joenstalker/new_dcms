<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StaffPermissionsTest extends TestCase
{
    use DatabaseMigrations;

    protected $tenant;

    /**
     * EnsureTenantSessionIsolation requires these keys; actingAs() alone is not enough for tenant routes.
     */
    protected function actingAsTenantUser(User $user): static
    {
        $this->actingAs($user);

        return $this->withSession([
            'tenant_authenticated' => true,
            'tenant_authenticated_tenant_id' => $this->tenant->id,
            'tenant_authenticated_user_id' => $user->id,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteTenantSqliteFiles();

        // Tenant HTTP context (subdomain must match domains table for routing/middleware)
        config(['app.url' => 'http://test-clinic.dcms.test']);
        $this->withServerVariables([
            'HTTP_HOST' => 'test-clinic.dcms.test',
            'SERVER_NAME' => 'test-clinic.dcms.test',
        ]);

        // 1. Setup Tenant
        $this->tenant = Tenant::create(['id' => 'test-clinic']);
        $this->tenant->domains()->create(['domain' => 'test-clinic.dcms.test']);

        $plan = SubscriptionPlan::create([
            'name' => 'Staff Test Plan',
            'slug' => 'staff-test-plan',
            'price_monthly' => 99,
            'stripe_monthly_price_id' => 'price_staff_test',
            'max_users' => 100,
            'max_patients' => 1000,
        ]);

        // Avoid pivot/feature rows (e.g. max_users = 1) overriding legacy limits for CheckSubscription.
        $plan->features()->detach();

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $plan->id,
            'stripe_status' => 'active',
            'payment_status' => 'paid',
            'billing_cycle' => 'monthly',
            'stripe_id' => 'sub_staff_test_001',
        ]);

        tenancy()->initialize($this->tenant);

        // 2. Run Tenant Migrations
        $this->artisan('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--realpath' => true,
        ]);

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        // Ensure cache is clear
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Illuminate\Support\Facades\Mail::fake();
    }

    protected function tearDown(): void
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            tenancy()->end();
        }

        $this->deleteTenantSqliteFiles();

        parent::tearDown();
    }

    /** @test */
    public function a_newly_created_staff_member_receives_base_dashboard_permissions()
    {
        // 1. Setup Owner
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        // 2. Act: Create a new staff member via the controller logic
        $this->actingAsTenantUser($owner)->from('http://test-clinic.dcms.test/staff')->post('http://test-clinic.dcms.test/staff', [
            'name' => 'Dr. Test',
            'email' => 'drtest@example.com',
            'role' => 'Dentist',
        ]);

        // 3. Assert: Verify the user exists and has the base permission
        $staff = User::where('email', 'drtest@example.com')->first();

        $this->assertNotNull($staff);
        $this->assertTrue($staff->hasRole('Dentist'));
        $this->assertTrue($staff->hasPermissionTo('view dashboard'));
    }

    /** @test */
    public function staff_with_only_view_patients_permission_cannot_create_patients()
    {
        // 1. Setup Staff with ONLY view permission
        $staff = User::factory()->create();
        $staff->assignRole('Dentist');
        Permission::firstOrCreate(['name' => 'view patients']);
        Permission::firstOrCreate(['name' => 'create patients']);
        $staff->syncPermissions(['view patients']);

        // 2. Act: Attempt to access create page and store patient
        $response = $this->actingAsTenantUser($staff)->get('http://test-clinic.dcms.test/patients/create');
        $response->assertStatus(403);

        $storeResponse = $this->actingAsTenantUser($staff)->post('http://test-clinic.dcms.test/patients', [
            'first_name' => 'Should',
            'last_name' => 'Fail',
        ]);
        $storeResponse->assertStatus(403);
    }

    /** @test */
    public function staff_with_create_services_can_access_service_store()
    {
        // 1. Setup Staff with create services permission
        $staff = User::factory()->create();
        Role::firstOrCreate(['name' => 'Assistant']);
        $staff->assignRole('Assistant');
        Permission::firstOrCreate(['name' => 'create services']);
        $staff->syncPermissions(['create services']);

        // 2. Act: Access store service
        $response = $this->actingAsTenantUser($staff)->post('http://test-clinic.dcms.test/services', [
            'name' => 'Test Service',
            'price' => 100,
        ]);

        // 3. Assert: Should not be 403 (it might fail other validation but not authorization)
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
