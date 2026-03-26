<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StaffPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup Roles and Permissions
        Role::create(['name' => 'Owner']);
        Role::create(['name' => 'Dentist']);
        Permission::create(['name' => 'view dashboard']);
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
    public function staff_without_clinical_permissions_see_onboarding_state()
    {
        // 1. Setup Staff with ONLY base access
        $staff = User::factory()->create();
        $staff->assignRole('Dentist');
        $staff->syncPermissions(['view dashboard']);

        // 2. Act: Access Dashboard as Staff
        $response = $this->actingAs($staff)->get(route('tenant.dashboard'));

        // 3. Assert: Verify Inertia data contains the onboarding flag
        $response->assertStatus(200);
        
        // We can't easily test the 'computed' property from PHP, 
        // but we can verify the permissions are passed correctly
        $response->assertInertia(fn ($page) => $page
            ->where('auth.user.permissions', ['view dashboard'])
        );
    }
}
