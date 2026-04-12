<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureTenantSessionIsolation;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Blocks executable uploads disguised as patient photos (and similar vectors).
 */
class FileUploadSecurityTest extends TestCase
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

        $this->host = 'sec-upload.dcms.test';
        config(['app.url' => 'http://'.$this->host]);
        $this->withServerVariables([
            'HTTP_HOST' => $this->host,
            'SERVER_NAME' => $this->host,
        ]);

        $this->tenant = Tenant::create(['id' => 'sec-upload', 'name' => 'Upload Tenant']);
        $this->tenant->domains()->create(['domain' => explode('.', $this->host)[0]]);

        $plan = SubscriptionPlan::create([
            'name' => 'Sec Upload Plan',
            'slug' => 'sec-upload-plan',
            'price_monthly' => 1,
            'stripe_monthly_price_id' => 'price_sec_upl',
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
            'stripe_id' => 'sub_sec_upl',
        ]);

        $this->provisionTenantSqliteAndMigrate($this->tenant);

        tenancy()->initialize($this->tenant);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->owner = User::factory()->create(['email' => 'owner-upl@example.test']);
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
     * Protects against: uploading a PHP script where only images are expected.
     */
    public function test_patient_store_rejects_non_image_photo_upload(): void
    {
        $malicious = UploadedFile::fake()->create('payload.php', 12, 'application/x-php');

        $this->actingAsTenantUser($this->owner, $this->tenant->id)
            ->from('http://'.$this->host.'/patients/create')
            ->post('http://'.$this->host.'/patients', [
                'first_name' => 'Mal',
                'last_name' => 'Ware',
                'photo' => $malicious,
            ])
            ->assertSessionHasErrors('photo');
    }

    /**
     * Protects against: oversized uploads exhausting disk or workers.
     */
    public function test_patient_store_rejects_oversized_image(): void
    {
        $tooBig = UploadedFile::fake()->image('huge.jpg')->size(3000);

        $this->actingAsTenantUser($this->owner, $this->tenant->id)
            ->from('http://'.$this->host.'/patients/create')
            ->post('http://'.$this->host.'/patients', [
                'first_name' => 'Big',
                'last_name' => 'File',
                'photo' => $tooBig,
            ])
            ->assertSessionHasErrors('photo');
    }

    /**
     * Protects against: regression where executable extensions slip past MIME checks.
     */
    public function test_patient_store_accepts_valid_small_image(): void
    {
        $image = UploadedFile::fake()->image('avatar.jpg', 120, 120);

        $response = $this->actingAsTenantUser($this->owner, $this->tenant->id)
            ->from('http://'.$this->host.'/patients/create')
            ->post('http://'.$this->host.'/patients', [
                'first_name' => 'Good',
                'last_name' => 'Image',
                'photo' => $image,
            ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();
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
