<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantProfileTest extends TestCase
{
    use DatabaseMigrations;

    protected Tenant $tenant;

    protected User $user;

    protected string $tenantDomain;

    protected function setUp(): void
    {
        parent::setUp();

        $tenantId = 'tenant-profile-' . uniqid();
        $this->tenantDomain = $tenantId . '.dcms.test';

        $this->tenant = Tenant::withoutEvents(function () use ($tenantId) {
            return Tenant::create([
                'id' => $tenantId,
                'name' => 'Tenant Profile Clinic',
                'email' => 'owner@tenant-profile.test',
            ]);
        });

        $this->tenant->domains()->create([
            'domain' => $this->tenantDomain,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Profile Plan',
            'slug' => 'profile-plan',
            'price_monthly' => 99,
            'stripe_plan_id' => 'price_profile_plan',
        ]);

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $plan->id,
            'stripe_status' => 'active',
            'payment_status' => 'paid',
            'billing_cycle' => 'monthly',
            'stripe_id' => 'sub_profile_001',
        ]);

        tenancy()->initialize($this->tenant);

        $this->artisan('migrate', [
            '--path' => 'database/migrations/tenant',
            '--realpath' => true,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Tenant Owner',
            'email' => 'owner@tenant-profile.test',
        ]);
    }

    protected function tearDown(): void
    {
        tenancy()->end();

        parent::tearDown();
    }

    public function test_tenant_profile_page_is_displayed(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->withSession([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) $this->tenant->id,
                'tenant_authenticated_user_id' => (int) $this->user->id,
            ])
            ->get('http://' . $this->tenantDomain . '/profile');

        $response
            ->assertOk()
            ->assertSee('"component":"Tenant/Profile/Edit"', false);
    }

    public function test_tenant_can_update_profile_picture(): void
    {
        Storage::fake('public');

        $response = $this
            ->actingAs($this->user)
            ->withSession([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) $this->tenant->id,
                'tenant_authenticated_user_id' => (int) $this->user->id,
            ])
            ->from('http://' . $this->tenantDomain . '/profile')
            ->post('http://' . $this->tenantDomain . '/profile/picture', [
                'image' => 'data:image/png;base64,' . base64_encode('tenant-profile-image'),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('http://' . $this->tenantDomain . '/profile');

        $this->user->refresh();

        $this->assertNotEmpty($this->user->profile_picture);
        Storage::disk('public')->assertExists($this->user->profile_picture);
    }

    public function test_tenant_can_update_password(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->withSession([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) $this->tenant->id,
                'tenant_authenticated_user_id' => (int) $this->user->id,
            ])
            ->from('http://' . $this->tenantDomain . '/profile')
            ->put('http://' . $this->tenantDomain . '/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('http://' . $this->tenantDomain . '/profile');

        $this->assertTrue(Hash::check('new-password', $this->user->refresh()->password));
    }

    public function test_preview_session_uses_tenant_preview_user_in_shared_auth_props(): void
    {
        tenancy()->end();

        $centralAdmin = User::factory()->create([
            'id' => 999,
            'name' => 'Central Admin',
            'email' => 'admin@central.test',
            'is_admin' => true,
        ]);

        tenancy()->initialize($this->tenant);

        $previewUser = $this->user;

        $response = $this
            ->actingAs($centralAdmin)
            ->withSession([
                'tenant_preview_active' => [
                    'active' => true,
                    'tenant_id' => (string) $this->tenant->id,
                    'tenant_user_id' => (int) $previewUser->id,
                ],
            ])
            ->get('http://' . $this->tenantDomain . '/dashboard');

        $response
            ->assertOk()
            ->assertSee('"name":"' . $previewUser->name . '"', false)
            ->assertDontSee('"name":"Central Admin"', false);
    }
}