<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TenantFeatureUpdate;
use App\Services\FeatureOTAUpdateService;
use App\Mail\PlanFeatureUpdateMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
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
    }

    #[Test]
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

        $this->provisionTenantSqliteAndMigrate($tenant);

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
        $batch = $otaService->pushFeatureUpdates($feature)->dispatch();

        // 4. Assert: Record created and email queued
        $this->assertNotNull($batch);
        $this->assertDatabaseHas('tenant_feature_updates', [
            'tenant_id' => $tenant->id,
            'feature_id' => $feature->id,
            'status' => TenantFeatureUpdate::STATUS_PENDING,
        ]);

        Mail::assertSent(PlanFeatureUpdateMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    }

    #[Test]
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

    #[Test]
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

        $this->provisionTenantSqliteAndMigrate($tenantA);
        $this->provisionTenantSqliteAndMigrate($tenantB);

        // 2. Act: Push updates manually
        $otaService = new FeatureOTAUpdateService();
        $batch = $otaService->pushPlanUpdates($plan)->dispatch();

        // 3. Assert: Both tenants should be analyzed/notified
        $this->assertNotNull($batch);
        $this->assertEquals(2, $batch->totalJobs);

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
        Mail::assertSent(\App\Mail\PlanFeatureUpdateMail::class, 2);
    }
}
