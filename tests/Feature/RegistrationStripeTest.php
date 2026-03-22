<?php

namespace Tests\Feature;

use App\Models\PendingRegistration;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Domain;
use App\Models\Subscription;
use App\Mail\RegistrationPending;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;
use Tests\TestCase;
use Mockery;

class RegistrationStripeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        
        // Mock Tenancy events
        \Illuminate\Support\Facades\Event::fake([
            \Stancl\Tenancy\Events\TenantCreated::class,
            \Stancl\Tenancy\Events\TenantSaved::class,
            \Stancl\Tenancy\Events\TenantUpdated::class,
            \Stancl\Tenancy\Events\DatabaseCreated::class,
        ]);

        // Force 'central' connection for SQLite tests
        $this->app['db']->extend('central', function () {
            return $this->app['db']->connection('sqlite');
        });
        config(['tenancy.database.auto_create' => false]);
    }

    /** @test */
    public function it_completes_registration_after_successful_stripe_payment()
    {
        // 1. Arrange
        $plan = SubscriptionPlan::create([
            'name' => 'Monthly Plan',
            'slug' => 'monthly',
            'price_monthly' => 100,
            'stripe_monthly_price_id' => 'price_123',
        ]);

        $pending = PendingRegistration::create([
            'clinic_name' => 'Test Clinic',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'subdomain' => 'test-clinic',
            'password' => 'password123',
            'verification_token' => 'test_token_123',
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $mockSessionId = 'sess_test_123';
        $mockSubscriptionId = 'sub_test_123';

        // Mock StripeClient using Laravel's mock helper
        $this->mock(StripeClient::class, function ($mock) use ($mockSessionId, $mockSubscriptionId, $pending) {
            $session = (object)[
                'id' => $mockSessionId,
                'status' => 'complete',
                'payment_status' => 'paid',
                'subscription' => $mockSubscriptionId,
                'amount_total' => 10000, // 100.00
                'metadata' => (object)[
                    'pending_registration_id' => $pending->id
                ]
            ];

            $sessionsService = Mockery::mock();
            $sessionsService->shouldReceive('retrieve')
                ->with($mockSessionId)
                ->andReturn($session);

            $checkoutService = Mockery::mock();
            $checkoutService->shouldReceive('__get')->with('sessions')->andReturn($sessionsService);
            $checkoutService->sessions = $sessionsService; // Direct property fallback

            $mock->shouldReceive('getService')->with('checkout')->andReturn($checkoutService);
            $mock->shouldReceive('__get')->with('checkout')->andReturn($checkoutService);
            $mock->checkout = $checkoutService; // Direct property fallback
        });

        // Mock NotificationService
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyAdmins')->once();
        });

        // 2. Act
        $response = $this->postJson('/registration/complete', [
            'session_id' => $mockSessionId,
        ]);

        // 3. Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify Tenant & Domain
        $tenant = Tenant::find('test-clinic');
        $this->assertNotNull($tenant);
        $this->assertEquals('Test Clinic', $tenant->name);
        $this->assertEquals('john@example.com', $tenant->email);

        $this->assertDatabaseHas('domains', [
            'domain' => 'test-clinic',
            'tenant_id' => 'test-clinic',
        ]);

        // Verify Subscription
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => 'test-clinic',
            'stripe_id' => $mockSubscriptionId,
            'stripe_status' => 'active',
        ]);

        // Verify Pending Registration is updated (though we stay in PENDING status as per logic)
        $this->assertDatabaseHas('pending_registrations', [
            'id' => $pending->id,
            'stripe_session_id' => $mockSessionId,
        ]);

        // Verify Email Sent
        Mail::assertSent(RegistrationPending::class, function ($mail) use ($pending) {
            return $mail->hasTo($pending->email);
        });
    }
}
