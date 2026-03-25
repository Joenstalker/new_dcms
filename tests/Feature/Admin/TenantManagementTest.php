<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Tenancy events
        \Illuminate\Support\Facades\Event::fake([
            \Stancl\Tenancy\Events\TenantCreated::class,
            \Stancl\Tenancy\Events\TenantSaved::class,
            \Stancl\Tenancy\Events\TenantUpdated::class,
            \Stancl\Tenancy\Events\DatabaseCreated::class,
        ]);

        // Force 'central' connection for SQLite tests (as seen in RegistrationStripeTest)
        $this->app['db']->extend('central', function () {
            return $this->app['db']->connection('sqlite');
        });
        config(['tenancy.database.auto_create' => false]);

        // Create a super admin user
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_admin' => true,
        ]);
    }

    /** @test */
    public function an_active_paying_tenant_cannot_be_deleted()
    {
        // 1. Arrange
        $tenant = Tenant::create([
            'id' => 'active-paying',
            'name' => 'Active Clinic',
            'status' => 'active',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'stripe_monthly_price_id' => 'price_123',
        ]);

        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'stripe_status' => 'active',
            'stripe_price' => 100,
            'billing_cycle' => 'monthly',
        ]);

        // 2. Act
        $this->actingAs($this->admin);
        $response = $this->delete("/admin/tenants/{$tenant->id}");

        // 3. Assert
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('tenants', ['id' => 'active-paying']);
    }

    /** @test */
    public function a_suspended_or_inactive_tenant_can_be_deleted()
    {
        // 1. Arrange
        $tenant = Tenant::create([
            'id' => 'safe-to-delete',
            'name' => 'Inactive Clinic',
            'status' => 'suspended',
        ]);

        // No active subscription

        // 2. Act
        $this->actingAs($this->admin);
        $response = $this->delete("/admin/tenants/{$tenant->id}");

        // 3. Assert
        $this->assertDatabaseMissing('tenants', ['id' => 'safe-to-delete']);
    }

    /** @test */
    public function updating_tenant_status_requires_a_reason()
    {
        // 1. Arrange
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Clinic',
            'status' => 'active',
        ]);
        
        $plan = SubscriptionPlan::create(['name' => 'Basic', 'slug' => 'basic']);

        // 2. Act
        $this->actingAs($this->admin);
        $response = $this->put("/admin/tenants/{$tenant->id}", [
            'name' => 'Updated Name',
            'owner_name' => 'Updated Owner',
            'status' => 'suspended',
            'plan_id' => $plan->id,
            // 'reason' is missing
        ]);

        // 3. Assert
        $response->assertSessionHasErrors(['reason']);
    }
}
