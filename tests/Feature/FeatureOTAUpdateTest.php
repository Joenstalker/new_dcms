<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TenantFeatureUpdate;
use App\Services\FeatureOTAUpdateService;
use App\Mail\NewFeatureUpdateMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FeatureOTAUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        \Illuminate\Support\Facades\Event::fake([
            \Stancl\Tenancy\Events\TenantCreated::class,
            \Stancl\Tenancy\Events\TenantSaved::class,
            \Stancl\Tenancy\Events\TenantUpdated::class,
            \Stancl\Tenancy\Events\DatabaseCreated::class,
        ]);

        // Force 'central' connection to be the EXACT SAME connection instance as 'sqlite'
        // so they share the same memory SQLite database and tables.
        $this->app['db']->extend('central', function () {
            return $this->app['db']->connection('sqlite');
        });
        
        // Prevent stancl/tenancy from trying to create physical databases during tests
        config(['tenancy.database.auto_create' => false]);
    }

    /** @test */
    public function admin_can_create_feature_and_notify_eligible_tenants()
    {
        // 1. Arrange: Create a plan and an active tenant subscription
        $plan = SubscriptionPlan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro',
            'price_monthly' => 100,
            'stripe_plan_id' => 'price_pro',
        ]);

        $tenant = Tenant::withoutEvents(function () {
            $t = new Tenant([
                'id' => 'test-clinic',
                'name' => 'Test Clinic',
                'email' => 'admin@test.com'
            ]);
            $t->save();
            return $t;
        });

        $admin = User::factory()->create(['email' => 'admin@test.com', 'is_admin' => true]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'stripe_status' => 'active',
            'stripe_id' => 'sub_123',
        ]);

        // 2. Create a new feature and assign it to the plan
        $feature = Feature::create([
            'key' => 'new_ota_feature',
            'name' => 'New Analytics',
            'type' => 'boolean',
            'is_active' => true,
        ]);

        $plan->features()->attach($feature->id, ['value_boolean' => true]);

        // 3. Act: Run the OTA service
        $otaService = new FeatureOTAUpdateService();
        $count = $otaService->createUpdateRecordsForEligibleTenants($feature);

        // 4. Assert: Record created and email queued
        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('tenant_feature_updates', [
            'tenant_id' => $tenant->id,
            'feature_id' => $feature->id,
            'status' => TenantFeatureUpdate::STATUS_PENDING,
        ]);

        Mail::assertQueued(NewFeatureUpdateMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    }

    /** @test */
    public function tenant_can_apply_a_pending_update()
    {
        // Arrange
        $tenant = Tenant::withoutEvents(function () {
            $t = new Tenant([
                'id' => 'test-clinic',
                'name' => 'Test Clinic',
                'email' => 'admin@test.com'
            ]);
            $t->save();
            return $t;
        });
        $feature = Feature::create([
            'key' => 'applied_feature',
            'name' => 'Applied Feature',
            'type' => 'boolean',
        ]);

        TenantFeatureUpdate::create([
            'tenant_id' => $tenant->id,
            'feature_id' => $feature->id,
            'status' => TenantFeatureUpdate::STATUS_PENDING,
        ]);

        // Act
        $otaService = new FeatureOTAUpdateService();
        $applied = $otaService->applyUpdate($tenant->id, [$feature->id]);

        // Assert
        $this->assertCount(1, $applied);
        $this->assertDatabaseHas('tenant_feature_updates', [
            'tenant_id' => $tenant->id,
            'feature_id' => $feature->id,
            'status' => TenantFeatureUpdate::STATUS_APPLIED,
        ]);
        $this->assertNotNull(TenantFeatureUpdate::where('feature_id', $feature->id)->first()->applied_at);
    }

    /** @test */
    public function admin_can_manually_push_features_for_a_plan_to_all_tenants()
    {
        // 1. Arrange: Plan, Features, and Two Tenants
        $plan = SubscriptionPlan::create([
            'name' => 'Premium Plan',
            'slug' => 'premium',
            'price_monthly' => 200,
            'stripe_plan_id' => 'price_premium',
        ]);

        $feature = Feature::create([
            'key' => 'premium_ota_feature',
            'name' => 'Premium Analytics',
            'type' => 'boolean',
            'is_active' => true,
        ]);

        $plan->features()->attach($feature->id, ['value_boolean' => true, 'pushed_at' => null]);

        // Tenant A: Subscribed to Premium
        $tenantA = Tenant::withoutEvents(function () {
            $t = new Tenant(['id' => 'clinic-a', 'name' => 'Clinic A', 'email' => 'adminA@test.com']);
            $t->save();
            return $t;
        });
        User::factory()->create(['email' => 'adminA@test.com', 'is_admin' => true]);
        Subscription::create([
            'tenant_id' => $tenantA->id,
            'subscription_plan_id' => $plan->id,
            'stripe_status' => 'active',
            'stripe_id' => 'sub_A123',
        ]);

        // Tenant B: Subscribed to Basic (Not Premium)
        $basicPlan = SubscriptionPlan::create([
            'name' => 'Basic Plan',
            'slug' => 'basic',
            'price_monthly' => 50,
            'stripe_plan_id' => 'price_basic',
        ]);
        $tenantB = Tenant::withoutEvents(function () {
            $t = new Tenant(['id' => 'clinic-b', 'name' => 'Clinic B', 'email' => 'adminB@test.com']);
            $t->save();
            return $t;
        });
        User::factory()->create(['email' => 'adminB@test.com', 'is_admin' => true]);
        Subscription::create([
            'tenant_id' => $tenantB->id,
            'subscription_plan_id' => $basicPlan->id,
            'stripe_status' => 'active',
            'stripe_id' => 'sub_B123',
        ]);

        // 2. Act: Push updates manually
        $otaService = new FeatureOTAUpdateService();
        $notifiedCount = $otaService->pushPlanUpdates($plan);

        // 3. Assert: Both tenants should be analyzed/notified
        $this->assertEquals(2, $notifiedCount);

        // Tenant A gets the actual update payload natively
        $this->assertDatabaseHas('tenant_feature_updates', [
            'tenant_id' => $tenantA->id,
            'feature_id' => $feature->id,
            'status' => TenantFeatureUpdate::STATUS_PENDING,
        ]);

        // Tenant B DOES NOT get the update payload natively (only an advertisement email)
        $this->assertDatabaseMissing('tenant_feature_updates', [
            'tenant_id' => $tenantB->id,
            'feature_id' => $feature->id,
        ]);

        // Mark Pivot as Pushed
        $pushedFeature = $plan->features()->where('feature_id', $feature->id)->first();
        $this->assertNotNull($pushedFeature->pivot->pushed_at);

        // Validate Mail Dispatch
        Mail::assertQueued(\App\Mail\PlanFeatureUpdateMail::class, 2);
    }
}
