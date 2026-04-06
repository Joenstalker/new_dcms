<?php

namespace Tests\Feature;

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

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Tenant
        $this->tenant = Tenant::create(['id' => 'test-clinic']);
        $this->tenant->domains()->create(['domain' => 'localhost']);
        tenancy()->initialize($this->tenant);

        // 2. Run Tenant Migrations
        $this->artisan('migrate', [
            '--path' => 'database/migrations/tenant',
            '--realpath' => true
        ]);

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        // Ensure cache is clear
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /** @test */
    public function a_newly_created_staff_member_receives_base_dashboard_permissions()
    {
        // 1. Setup Owner
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        // 2. Act: Create a new staff member via the controller logic
        $this->actingAs($owner)->post(route('staff.store'), [
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
        $response = $this->actingAs($staff)->get(route('patients.create'));
        $response->assertStatus(403);

        $storeResponse = $this->actingAs($staff)->post(route('patients.store'), [
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
        $response = $this->actingAs($staff)->post(route('services.store'), [
            'name' => 'Test Service',
            'price' => 100,
        ]);

        // 3. Assert: Should not be 403 (it might fail other validation but not authorization)
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
