<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantProfileTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected string $tenantDomain;

    protected function setUp(): void
    {
        parent::setUp();

        $tenantId = 'tenant-profile-' . uniqid();
        $this->tenantDomain = $tenantId . '.dcms.test';

        config(['app.url' => 'http://'.$this->tenantDomain]);
        $this->withServerVariables([
            'HTTP_HOST' => $this->tenantDomain,
            'SERVER_NAME' => $this->tenantDomain,
        ]);

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
            'stripe_monthly_price_id' => 'price_profile_plan',
            'max_users' => 100,
            'max_patients' => 1000,
        ]);

        $plan->features()->detach();

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $plan->id,
            'stripe_status' => 'active',
            'payment_status' => 'paid',
            'billing_cycle' => 'monthly',
            'stripe_id' => 'sub_profile_001',
        ]);

        $dbName = $this->tenant->database_name;
        if ($dbName && ! is_file(database_path($dbName))) {
            touch(database_path($dbName));
        }

        tenancy()->initialize($this->tenant);

        $this->artisan('migrate', [
            '--database' => 'tenant',
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
        if (function_exists('tenancy') && tenancy()->initialized) {
            tenancy()->end();
        }

        $this->deleteTenantSqliteFiles();

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

        $response->assertOk();
        // Inertia embeds the component name as JSON (forward slashes escaped as \/).
        $this->assertStringContainsString('Tenant\\/Profile\\/Edit', $response->getContent());
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
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('http://' . $this->tenantDomain . '/profile');

        $this->assertTrue(Hash::check('NewPassword1!', $this->user->refresh()->password));
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
            ->assertSee($previewUser->name, false)
            ->assertDontSee('Central Admin', false);
    }
}